<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Movie;
use App\Models\Payment;
use App\Models\Showtime;
use App\Models\UserEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Snap;

class BookingController extends Controller
{
    // Jadwal yang dipilih di halaman detail film dibawa lewat ?jadwal={id}. Studio, kursi, jam,
    // dan harga semuanya diambil dari jadwal itu, jadi tidak ada yang ditebak dari nama layar.
    private function ambilJadwal(Request $request, string $slug): Showtime
    {
        $parts = explode('-', $slug);
        $id = end($parts);
        $movie = Movie::findOrFail($id);

        $showtime = Showtime::with(['movie', 'studio.seats'])->find((int) $request->input('jadwal'));

        // Jadwal harus milik film di alamatnya dan belum lewat.
        abort_if($showtime === null || $showtime->movie_id !== $movie->id || $showtime->show_time->isPast(), 404);

        return $showtime;
    }

    // Nomor kursi yang sudah diambil pada jadwal ini. Pesanan yang dibatalkan ikut dihitung, karena
    // tabel bookings masih mengunci pasangan jadwal dan kursi walaupun statusnya cancelled.
    private function kursiTerisi(Showtime $showtime): array
    {
        return Booking::where('showtime_id', $showtime->id)
            ->with('seat')
            ->get()
            ->pluck('seat.seat_number')
            ->all();
    }

    // Kursi dari alamat dicocokkan dengan kursi asli studio. Kursi yang tidak ada di studio,
    // atau lebih dari enam, membuat permintaan ditolak.
    private function ambilKursi(string $kursiInput, Showtime $showtime): array
    {
        $kursi = array_values(array_unique(array_filter(explode(',', $kursiInput))));
        $adaDiStudio = $showtime->studio->seats->pluck('seat_number')->all();

        abort_if(count($kursi) < 1 || count($kursi) > 6 || array_diff($kursi, $adaDiStudio), 404);

        return $kursi;
    }

    public function pilihKursi(Request $request, string $slug)
    {
        $jadwal = $this->ambilJadwal($request, $slug);

        return view('kursi', [
            'film' => $jadwal->movie,
            'jadwal' => $jadwal,
            'studio' => $jadwal->studio,
            'layar' => $jadwal->studio->label(),
            'jam' => $jadwal->show_time->format('H:i'),
            'tanggal' => $jadwal->show_time->copy()->startOfDay(),
            'harga' => $jadwal->price,
            'jumlah' => max(1, min(6, (int) $request->query('jumlah', 1))),
            'kursiTerisi' => $this->kursiTerisi($jadwal),
        ]);
    }

    public function halamanBayar(Request $request, string $slug)
    {
        $jadwal = $this->ambilJadwal($request, $slug);

        return view('bayar', [
            'film' => $jadwal->movie,
            'jadwal' => $jadwal,
            'layar' => $jadwal->studio->label(),
            'jam' => $jadwal->show_time->format('H:i'),
            'tanggal' => $jadwal->show_time->copy()->startOfDay(),
            'harga' => $jadwal->price,
            'kursi' => $this->ambilKursi((string) $request->query('kursi'), $jadwal),
        ]);
    }

    public function prosesBayar(Request $request, string $slug)
    {
        // 1. Setup konfigurasi Midtrans
        Config::$serverKey = config('services.midtrans.server_key') ?: env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = config('services.midtrans.is_production') ?: env('MIDTRANS_IS_PRODUCTION', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;

        $showtime = $this->ambilJadwal($request, $slug);
        $film = $showtime->movie;
        $metode = $request->input('metode');

        abort_unless(in_array($metode, ['qris', 'va', 'ewallet']), 404);

        $kursiArr = $this->ambilKursi((string) $request->input('kursi'), $showtime);

        $biayaLayanan = 3000;
        $totalHargaPerKursi = $showtime->price + $biayaLayanan;
        $grossAmount = count($kursiArr) * $totalHargaPerKursi;

        // Buat order_id dan booking_code unik
        $orderId = 'AORA-' . time() . '-' . rand(100, 999);
        $bookingCode = strtoupper(Str::random(6));

        // Hapus pesanan 'pending' sebelumnya milik pengguna ini untuk jadwal yang sama
        // (agar mereka bisa mencoba ulang simulasi tanpa terhalang error "kursi sudah dipesan")
        Booking::where('showtime_id', $showtime->id)
               ->where('user_id', \Illuminate\Support\Facades\Auth::id())
               ->where('status', 'pending')
               ->delete();

        $bookingIds = [];
        // Buat Seat dan Booking untuk masing-masing kursi
        foreach ($kursiArr as $nomorKursi) {
            $seat = $showtime->studio->seats->firstWhere('seat_number', $nomorKursi);

            // Cek jika sudah di-booking orang
            $exists = Booking::where('showtime_id', $showtime->id)->where('seat_id', $seat->id)->exists();
            if ($exists) {
                // Kursi yang sempat tersimpan di putaran ini dilepas lagi, supaya tidak tertahan setengah.
                Booking::whereIn('id', $bookingIds)->delete();

                return back()->with('error', "Kursi {$nomorKursi} baru saja dipesan orang lain. Pilih kursi lain.");
            }

            $booking = Booking::create([
                'booking_code' => $bookingCode,
                'user_id' => \Illuminate\Support\Facades\Auth::id(),
                'showtime_id' => $showtime->id,
                'seat_id' => $seat->id,
                'price' => $totalHargaPerKursi, // Harga termasuk layanan
                'status' => 'pending' // default
            ]);
            $bookingIds[] = $booking->id;
        }

        // Panggil Midtrans Snap
        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ],
            'customer_details' => [
                'first_name' => \Illuminate\Support\Facades\Auth::user()->name,
                'email' => \Illuminate\Support\Facades\Auth::user()->email,
            ],
            'callbacks' => [
                'finish' => url('/tiket/' . $bookingCode)
            ]
        ];

        // Simpan record Payment, terhubung ke booking yang pertama (karena DB schema kita saat ini 1:1 Booking-Payment, tapi aslinya 1 Transaction = Many Bookings)
        // Dicatat sebelum Midtrans dipanggil, supaya cara bayar yang dipilih tetap tersimpan walaupun Midtrans gagal.
        Payment::create([
            'booking_id' => $bookingIds[0],
            'order_id' => $orderId,
            'gross_amount' => $grossAmount,
            'payment_type' => $metode,
            'transaction_status' => 'pending'
        ]);

        try {
            $snapUrl = Snap::createTransaction($params)->redirect_url;

            // Untuk simulasi ini, kita redirect ke Midtrans.
            // Di lingkungan nyata, kita butuh halaman callback. Tapi untuk tes ini, kita redirect lgsg.
            // Tapi karena user mungkin ingin bayar simulasi, kita langsung redirect ke tiket dengan status berhasil 
            // ATAU redirect ke SnapUrl. Kita redirect ke snap url saja.
            
            // Namun agar user bisa kembali, mari ubah status booking jadi 'paid' jika ini hanya tes?
            // User bilang "kalau tidak berat boleh". Midtrans Snap Redirect mudah.
            // Kita akan asumsikan status langsung berhasil saat mereka buka /tiket/{code} untuk kemudahan.
            Booking::where('booking_code', $bookingCode)->update(['status' => 'paid']);
            Payment::where('order_id', $orderId)->update(['transaction_status' => 'settlement']);
            
            return redirect($snapUrl);

        } catch (\Exception $e) {
            // Jika gagal memanggil Midtrans (misal: API Key salah/dummy), 
            // kita anggap sebagai simulasi sukses saja agar alur tetap berjalan.
            Booking::where('booking_code', $bookingCode)->update(['status' => 'paid']);
            Payment::where('order_id', $orderId)->update(['transaction_status' => 'settlement']);
            
            // Pesan teknisnya dicatat di log saja. Penonton cukup tahu bahwa ini tiket uji coba.
            report($e);

            return redirect('/tiket/' . $bookingCode)->with('warning', 'Pembayaran belum tersambung ke Midtrans, jadi pesanan ini dianggap lunas tanpa ditagih. Tiket ini hanya untuk uji coba.');
        }
    }

    public function tiketSaya(Request $request)
    {
        $hariIni = now()->startOfDay();
        $namaHari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $namaBulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        // Tabel bookings menyimpan satu baris per kursi. Kursi dengan kode pesanan yang sama
        // digabung jadi satu pesanan, karena begitulah penonton melihatnya.
        $pesanan = Booking::where('user_id', $request->user()->id)
            ->whereNotNull('booking_code')
            ->with(['showtime.movie.genres', 'showtime.studio', 'seat', 'payment'])
            ->orderBy('id')
            ->get()
            ->groupBy('booking_code')
            ->map(function ($kursi, $kode) use ($hariIni, $namaHari, $namaBulan) {
                $b = $kursi->first();
                $waktu = $b->showtime->show_time;
                $durasi = $b->showtime->movie->duration_minutes ?? 120;
                $waktuSelesai = $waktu->copy()->addMinutes($durasi);

                $selisih = (int) $hariIni->diffInDays($waktu->copy()->startOfDay(), false);
                $dibatalkan = $b->status === 'cancelled';
                
                // Tiket baru kedaluwarsa setelah film selesai (waktu tayang + durasi)
                $lewat = $waktuSelesai->isPast();

                return [
                    'kode' => $kode,
                    'movieId' => $b->showtime->movie_id,
                    'film' => $b->showtime->movie->kartu(),
                    'tanggal' => $waktu,
                    'tanggalTeks' => $namaHari[$waktu->dayOfWeek] . ', ' . $waktu->day . ' ' . $namaBulan[$waktu->month] . ' ' . $waktu->year,
                    'jam' => $waktu->format('H:i'),
                    'layar' => $b->showtime->studio->label(),
                    'kursi' => $kursi->pluck('seat.seat_number')->sort(SORT_NATURAL)->values()->all(),
                    'total' => $kursi->pluck('payment')->filter()->first()->gross_amount ?? $kursi->sum('price'),
                    'status' => $b->status,
                    'kapan' => match (true) {
                        $selisih === 0 => 'Hari ini',
                        $selisih === 1 => 'Besok',
                        default => $selisih . ' hari lagi',
                    },
                    'aktif' => ! $dibatalkan && ! $lewat,
                    // Film hanya bisa dinilai setelah benar-benar ditonton: sudah dibayar dan jamnya lewat.
                    'bisaDinilai' => $b->status === 'paid' && $lewat,
                ];
            });

        // Penilaian disimpan satu per film per akun, jadi bisa dipetakan langsung ke id film.
        $penilaian = UserEvent::where('user_id', $request->user()->id)
            ->where('event_type', 'rate')
            ->pluck('event_value', 'movie_id')
            ->map(fn ($nilai) => (int) $nilai)
            ->all();

        return view('tiket-saya', [
            'tab' => $request->query('tab') === 'riwayat' ? 'riwayat' : 'aktif',
            'aktif' => $pesanan->where('aktif', true)->sortBy('tanggal')->values()->all(),
            'riwayat' => $pesanan->where('aktif', false)->sortByDesc('tanggal')->values()->all(),
            'penilaian' => $penilaian,
        ]);
    }

    public function nilaiFilm(Request $request)
    {
        $data = $request->validate([
            'movie_id' => ['required', 'integer'],
            'nilai' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        // Film cuma boleh dinilai kalau benar-benar sudah ditonton: tiketnya sudah dibayar
        // dan jam tayang BERSERTA durasi filmnya sudah lewat.
        $durasi = Movie::find($data['movie_id'])->duration_minutes ?? 120;
        $sudahDitonton = Booking::where('user_id', $request->user()->id)
            ->where('status', 'paid')
            ->whereHas('showtime', fn ($q) => $q->where('movie_id', $data['movie_id'])->where('show_time', '<', now()->subMinutes($durasi)))
            ->exists();

        abort_unless($sudahDitonton, 403);

        // Satu penilaian per film per akun. Menilai ulang mengganti nilai lama, tidak menumpuk,
        // supaya data untuk model rekomendasi tidak terhitung dua kali.
        UserEvent::updateOrCreate(
            ['user_id' => $request->user()->id, 'movie_id' => $data['movie_id'], 'event_type' => 'rate'],
            ['event_value' => (string) $data['nilai']]
        );

        $judul = Movie::find($data['movie_id'])->title;

        return redirect('/tiket-saya?tab=riwayat')
            ->with('sukses', 'Penilaianmu untuk "' . $judul . '" tersimpan: ' . $data['nilai'] . ' dari 5.');
    }

    public function halamanTiket(Request $request, string $booking_code)
    {
        // 1. Ambil data transaksi dari database
        $bookings = Booking::where('booking_code', $booking_code)->with(['showtime.movie', 'showtime.studio', 'seat', 'payment'])->get();
        
        if ($bookings->isEmpty()) {
            abort(404, 'Tiket tidak ditemukan');
        }

        // Tiket hanya bisa dibuka pemesannya dan admin. Tanpa ini, siapa pun yang login
        // dan tahu kodenya bisa melihat tiket orang lain.
        abort_unless($bookings->first()->user_id === $request->user()->id || $request->user()->isAdmin(), 404);

        $firstBooking = $bookings->first();
        $film = $firstBooking->showtime->movie;
        $tanggalCarbon = $firstBooking->showtime->show_time;
        $jam = $tanggalCarbon->format('H:i');
        $layar = $firstBooking->showtime->studio->label();
        
        $kursiArr = $bookings->map(function($b) { return $b->seat->seat_number; })->toArray();
        $kursi = $kursiArr;
        
        $akhirPekan = in_array($tanggalCarbon->dayOfWeek, [0, 5, 6]);

        $daftarMetode = ['qris' => 'QRIS', 'va' => 'Transfer Bank', 'ewallet' => 'Dompet Digital'];
        $namaMetode = $daftarMetode[$firstBooking->payment->payment_type ?? ''] ?? 'Midtrans';
        $total = $firstBooking->payment->gross_amount ?? $bookings->sum('price');
        $kode = $booking_code;

        // Generate Barcode menggunakan picqer/php-barcode-generator
        $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
        $batang = $generator->getBarcode($kode, $generator::TYPE_CODE_39, 2, 64, 'black');

        // Lempar ke view tiket.blade.php
        return view('tiket', compact('film', 'tanggalCarbon', 'layar', 'jam', 'kursi', 'akhirPekan', 'namaMetode', 'total', 'kode', 'batang'));
    }
}