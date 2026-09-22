<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Movie;
use App\Models\Payment;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\Studio;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Snap;

class BookingController extends Controller
{
    public function pilihKursi(Request $request, string $slug)
    {
        // 1. Cari film berdasarkan slug URL (misal: 'coyote-vs-acme-3' -> ambil angka 3)
        $parts = explode('-', $slug);
        $id = end($parts);
        $movie = Movie::findOrFail($id);

        // 2. Tangkap parameter dari URL yang dikirim oleh halaman detail film
        $layar = $request->query('layar'); // Contoh: "Regular 2D" atau "IMAX"
        $jam = $request->query('jam');
        $tanggal = $request->query('tanggal');
        $jumlahTiket = $request->query('jumlah', 1);

        // 3. Tentukan Studio berdasarkan jenis layar yang dipilih
        $namaStudio = str_contains(strtolower($layar), 'imax') ? 'Studio 2 (IMAX)' : 'Studio 1 (Regular)';
        
        // Ambil studio beserta seluruh kursinya (A1 - E10)
        $studio = Studio::with('seats')->where('name', $namaStudio)->firstOrFail();

        // 4. Ambil jadwal tayang (Showtime) untuk film dan studio ini
        $showtimeDatetime = Carbon::parse($tanggal . ' ' . $jam);
        $showtime = Showtime::where('movie_id', $movie->id)
                            ->where('studio_id', $studio->id)
                            ->where('show_time', $showtimeDatetime)
                            ->first();

        // 5. Cari ID kursi yang sudah dipesan orang lain pada jadwal tersebut
        $bookedSeatIds = Booking::where('showtime_id', $showtime->id ?? 0)
                                ->pluck('seat_id')
                                ->toArray();

        // Ubah ID kursi menjadi array nomor kursi (misal: ['A1', 'A2', 'C5']) agar mudah dibaca frontend
        $kursiTerisi = $studio->seats->whereIn('id', $bookedSeatIds)->pluck('seat_number')->toArray();

        // Parse tanggal ke Carbon object agar format() jalan di view
        $tanggalCarbon = Carbon::parse($tanggal);

        // 6. Lempar semua data ini ke teman frontend Anda di file kursi.blade.php
        return view('kursi', [
            'film' => $movie, 
            'studio' => $studio, 
            'layar' => $layar, 
            'jam' => $jam, 
            'tanggal' => $tanggalCarbon, 
            'jumlah' => $jumlahTiket, 
            'kursiTerisi' => $kursiTerisi
        ]);
    }

    public function halamanBayar(Request $request, string $slug)
    {
        // 1. Ambil ID dari slug URL
        $parts = explode('-', $slug);
        $id = end($parts);
        $film = Movie::findOrFail($id); // Sekarang ambil dari database!

        // 2. Tangkap parameter dari URL
        $layar = $request->query('layar');
        $jam = $request->query('jam');
        $tanggal = Carbon::parse($request->query('tanggal'));
        
        // 3. Tangkap deretan kursi, contoh: "F7,F8,F9" diubah jadi array ['F7', 'F8', 'F9']
        $kursiInput = $request->query('kursi');
        $kursi = $kursiInput ? explode(',', $kursiInput) : [];

        // 4. Hitung akhir pekan (untuk logika harga kalau ada)
        $akhirPekan = in_array($tanggal->dayOfWeek, [0, 5, 6]);

        $daftarMetode = ['qris' => 'QRIS', 'va' => 'Transfer Bank', 'ewallet' => 'Dompet Digital'];
        $namaMetode = $daftarMetode[$request->query('metode')] ?? 'Belum dipilih';

        // Lempar ke view bayar.blade.php
        return view('bayar', compact('film', 'tanggal', 'layar', 'jam', 'kursi', 'akhirPekan', 'namaMetode'));
    }

    public function prosesBayar(Request $request, string $slug)
    {
        // 1. Setup konfigurasi Midtrans
        Config::$serverKey = config('services.midtrans.server_key') ?: env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = config('services.midtrans.is_production') ?: env('MIDTRANS_IS_PRODUCTION', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;

        $parts = explode('-', $slug);
        $id = end($parts);
        $film = Movie::findOrFail($id);

        $layar = $request->input('layar');
        $jam = $request->input('jam');
        $tanggal = Carbon::parse($request->input('tanggal'));
        $kursiInput = $request->input('kursi');
        $metode = $request->input('metode');

        if (empty($kursiInput)) {
            return back()->with('error', 'Pilih minimal satu kursi.');
        }

        $kursiArr = explode(',', $kursiInput);
        
        $akhirPekan = in_array($tanggal->dayOfWeek, [0, 5, 6]);
        $tarif = require resource_path('data/tarif.php');
        $harga = $tarif[$layar][$akhirPekan ? 'akhirPekan' : 'biasa'];
        $biayaLayanan = 3000;
        $totalHargaPerKursi = $harga + $biayaLayanan;
        $grossAmount = count($kursiArr) * $totalHargaPerKursi;

        // Cari atau buat Studio
        $namaStudio = str_contains(strtolower($layar), 'imax') ? 'Studio 2 (IMAX)' : 'Studio 1 (Regular)';
        $studio = Studio::firstOrCreate(
            ['name' => $namaStudio],
            ['capacity' => 50]
        );

        // Cari atau buat Showtime
        $showtimeDatetime = Carbon::parse($tanggal->format('Y-m-d') . ' ' . $jam);
        $showtime = Showtime::firstOrCreate(
            [
                'movie_id' => $film->id,
                'studio_id' => $studio->id,
                'show_time' => $showtimeDatetime,
            ],
            ['price' => $harga]
        );

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
            $seat = Seat::firstOrCreate([
                'studio_id' => $studio->id,
                'seat_number' => $nomorKursi
            ]);

            // Cek jika sudah di-booking orang
            $exists = Booking::where('showtime_id', $showtime->id)->where('seat_id', $seat->id)->exists();
            if ($exists) {
                return back()->with('error', "Kursi {$nomorKursi} sudah dipesan orang lain.");
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

        try {
            $snapUrl = Snap::createTransaction($params)->redirect_url;
            
            // Simpan record Payment, terhubung ke booking yang pertama (karena DB schema kita saat ini 1:1 Booking-Payment, tapi aslinya 1 Transaction = Many Bookings)
            Payment::create([
                'booking_id' => $bookingIds[0], 
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
                'payment_type' => $metode,
                'transaction_status' => 'pending'
            ]);

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
            
            return redirect('/tiket/' . $bookingCode)->with('warning', 'Peringatan: Midtrans gagal diakses (' . $e->getMessage() . '). Menggunakan simulasi lokal.');
        }
    }

    public function halamanTiket(Request $request, string $booking_code)
    {
        // 1. Ambil data transaksi dari database
        $bookings = Booking::where('booking_code', $booking_code)->with(['showtime.movie', 'showtime.studio', 'seat', 'payment'])->get();
        
        if ($bookings->isEmpty()) {
            abort(404, 'Tiket tidak ditemukan');
        }

        $firstBooking = $bookings->first();
        $film = $firstBooking->showtime->movie;
        $tanggalCarbon = $firstBooking->showtime->show_time;
        $jam = $tanggalCarbon->format('H:i');
        $layar = $firstBooking->showtime->studio->name;
        
        $kursiArr = $bookings->map(function($b) { return $b->seat->seat_number; })->toArray();
        $kursi = $kursiArr;
        
        $akhirPekan = in_array($tanggalCarbon->dayOfWeek, [0, 5, 6]);

        $namaMetode = $firstBooking->payment->payment_type ?? 'Midtrans';
        $total = $firstBooking->payment->gross_amount ?? $bookings->sum('price');
        $kode = $booking_code;

        // Generate Barcode menggunakan picqer/php-barcode-generator
        $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
        $batang = $generator->getBarcode($kode, $generator::TYPE_CODE_39, 2, 64, 'black');

        // Lempar ke view tiket.blade.php
        return view('tiket', compact('film', 'tanggalCarbon', 'layar', 'jam', 'kursi', 'akhirPekan', 'namaMetode', 'total', 'kode', 'batang'));
    }
}