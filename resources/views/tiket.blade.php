@extends('layouts.app')

@section('judul', 'Tiket, ' . $film['judul'])

@section('konten')

    @php
        $namaHari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $namaBulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        [$th, $bl, $hr] = explode('-', $tanggal->format('Y-m-d'));
        $tanggalTeks = $namaHari[$tanggal->dayOfWeek] . ', ' . (int) $hr . ' ' . $namaBulan[(int) $bl] . ' ' . $th;

        $tarif = require resource_path('data/tarif.php');
        $harga = $tarif[$layar][$akhirPekan ? 'akhirPekan' : 'biasa'];

        $jumlah = count($kursi);
        $total = $jumlah * ($harga + 3000);

        // Kode dibuat tetap dari isi pesanan, jadi memuat ulang halaman tidak
        // mengubah kodenya. Nanti kode ini disimpan di kolom booking_code.
        $kode = kodePesanan($film['slug'], $tanggal->format('Y-m-d'), $jam, $layar, $kursi);

        // Code 39: standar kode batang yang bisa digambar sendiri tanpa paket tambahan.
        // Tiap karakter jadi 9 elemen berselang-seling batang dan spasi, tiga di antaranya lebar.
        // Karakter '*' dipakai sebagai penanda awal dan akhir, itu ketentuan standarnya.
        $pola39 = [
            '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn',
            '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw',
            '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn', 'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw',
            'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw', 'E' => 'wnnnwwnnn', 'F' => 'nnwnwwnnn',
            'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn', 'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn',
            'K' => 'wnnnnnnww', 'L' => 'nnwnnnnww', 'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww',
            'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn', 'Q' => 'nnnnnnwww', 'R' => 'wnnnnnwwn',
            'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn', 'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw',
            'W' => 'wwwnnnnnn', 'X' => 'nwnnwnnnw', 'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn',
            '-' => 'nwnnnnwnw', '.' => 'wwnnnnwnn', '*' => 'nwnnwnwnn',
        ];

        $sempit = 2;
        $lebar = 6;
        $tinggiBatang = 64;
        $zonaSunyi = 20;   // ruang kosong wajib di kiri dan kanan supaya pemindai mengenali awal kode

        $batang = '';
        $x = $zonaSunyi;

        foreach (str_split('*' . $kode . '*') as $huruf) {
            foreach (str_split($pola39[$huruf]) as $urutan => $elemen) {
                $tebal = $elemen === 'w' ? $lebar : $sempit;

                // Elemen berindeks genap adalah batang, yang ganjil adalah spasi
                if ($urutan % 2 === 0) {
                    $batang .= '<rect x="' . $x . '" y="0" width="' . $tebal . '" height="' . $tinggiBatang . '"/>';
                }

                $x += $tebal;
            }

            $x += $sempit;   // celah antar karakter, selalu sempit
        }

        $lebarTotal = $x - $sempit + $zonaSunyi;
    @endphp

    <p class="border-b border-nema-line/40 bg-nema-surface px-4 py-3 text-center text-sm text-nema-muted sm:px-6">
        Tiket contoh. Tidak ada pembayaran yang terjadi dan pesanan ini tidak tersimpan.
    </p>

    <div class="mx-auto max-w-lg px-4 py-10 sm:px-6">

        <h1 class="text-center text-2xl sm:text-3xl">Tiketmu siap</h1>

        <p class="mt-2 text-center text-sm text-nema-muted">
            Tunjukkan kode ini ke petugas di pintu masuk.
        </p>

        <div data-tiket class="mt-8 overflow-hidden rounded-2xl bg-nema-surface">

            <div class="p-6 sm:p-8">
                <p class="text-center text-xs text-nema-muted">Kode pesanan</p>

                {{-- Alasnya putih dan batangnya hitam karena pemindai butuh kontras setinggi
                     mungkin. Kode batang di atas latar gelap sering gagal terbaca. --}}
                <div class="mt-4 rounded-lg bg-white p-3">
                    <svg viewBox="0 0 {{ $lebarTotal }} {{ $tinggiBatang }}" width="100%" height="64"
                         preserveAspectRatio="none" fill="#000" role="img"
                         aria-label="Kode batang untuk kode pesanan {{ $kode }}">
                        {!! $batang !!}
                    </svg>
                </div>

                <p class="mt-4 text-center font-mono text-2xl font-semibold tracking-widest sm:text-3xl">{{ $kode }}</p>
            </div>

            {{-- Garis putus-putus meniru sobekan tiket, penanda batas antara kode dan rinciannya --}}
            <div data-sobek class="border-t border-dashed border-nema-line"></div>

            <dl class="grid grid-cols-2 gap-x-4 gap-y-5 p-6 text-sm sm:p-8">
                <div class="col-span-2">
                    <dt class="text-nema-muted">Film</dt>
                    <dd class="mt-1 text-base">{{ $film['judul'] }}</dd>
                </div>
                <div>
                    <dt class="text-nema-muted">Tanggal</dt>
                    <dd class="mt-1">{{ $tanggalTeks }}</dd>
                </div>
                <div>
                    <dt class="text-nema-muted">Jam</dt>
                    <dd class="mt-1">{{ $jam }}</dd>
                </div>
                <div>
                    <dt class="text-nema-muted">Studio</dt>
                    <dd class="mt-1">{{ $layar }}</dd>
                </div>
                <div>
                    <dt class="text-nema-muted">Kursi</dt>
                    <dd class="mt-1">{{ implode(', ', $kursi) }}</dd>
                </div>
                <div>
                    <dt class="text-nema-muted">Cara bayar</dt>
                    <dd class="mt-1">{{ $namaMetode }}</dd>
                </div>
                <div>
                    <dt class="text-nema-muted">Total dibayar</dt>
                    <dd class="mt-1">Rp {{ number_format($total, 0, ',', '.') }}</dd>
                </div>
            </dl>

        </div>

        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <button type="button" data-cetak
                    class="inline-flex min-h-11 items-center rounded-md bg-nema-maroon px-6 font-medium text-white transition-colors hover:bg-nema-maroon-hover">
                Cetak atau simpan PDF
            </button>

            <a href="{{ url('/') }}"
               class="inline-flex min-h-11 items-center rounded-md border border-nema-line px-6 transition-colors hover:bg-nema-surface">
                Kembali ke beranda
            </a>
        </div>

        <p class="mt-6 text-center text-xs text-nema-muted">
            Kode batang yang bisa dipindai belum ada. Itu butuh paket tambahan di
            <code>composer.json</code>, jadi perlu dibicarakan dengan Adam dulu.
        </p>

    </div>

    <style>
        @media print {
            @page {
                margin: 18mm;
            }

            /* Semua disembunyikan dulu, lalu kartu tiketnya saja yang ditampilkan lagi.
               Cara ini lebih tahan banting daripada menyembunyikan satu per satu elemen. */
            body * {
                visibility: hidden;
            }

            [data-tiket],
            [data-tiket] * {
                visibility: visible;
            }

            [data-tiket] {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                margin: 0;
                border: 1px solid #000;
                border-radius: 0;

                /* Dicetak terang, bukan gelap. Latar hitam boros tinta dan bikin
                   kode pesanannya buram di kebanyakan printer. */
                background: #fff;
                color: #000;
            }

            [data-tiket] * {
                color: #000 !important;
            }

            [data-tiket] dt {
                color: #555 !important;
            }

            [data-sobek] {
                border-color: #000 !important;
            }
        }
    </style>

    <script>
        document.querySelector('[data-cetak]').addEventListener('click', function () {
            window.print();
        });
    </script>

@endsection
