@extends('layouts.app')

@section('judul', $film['judul'] . ', AoraNema')

@section('konten')

    @php
        $namaHari = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        $namaBulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $tanggalIndo = function (string $iso) use ($namaBulan) {
            [$tahun, $bln, $hari] = explode('-', $iso);
            return (int) $hari . ' ' . $namaBulan[(int) $bln] . ' ' . $tahun;
        };

        $hariIni = now()->startOfDay();

        $daftarTanggal = [];
        for ($i = 0; $i < 6; $i++) {
            $daftarTanggal[] = $hariIni->copy()->addDays($i);
        }

        $geser = $hariIni->diffInDays($tanggal) * 15;

        $tambahMenit = function (string $jam, int $menit) {
            [$j, $m] = explode(':', $jam);
            $total = ((int) $j * 60 + (int) $m + $menit) % 1440;
            return sprintf('%02d:%02d', intdiv($total, 60), $total % 60);
        };

        // Akhir pekan dihitung Jumat sampai Minggu, seperti kebanyakan bioskop di Indonesia.
        $akhirPekan = in_array($tanggal->dayOfWeek, [5, 6, 0]);

        // Harga per format layar. Nanti semua angka ini datang dari kolom price di showtimes.
        $daftarHarga = [
            'Regular 2D' => ['biasa' => 45000, 'akhirPekan' => 55000],
            'Regular 3D' => ['biasa' => 55000, 'akhirPekan' => 65000],
            'IMAX' => ['biasa' => 85000, 'akhirPekan' => 100000],
            'Premiere 2D' => ['biasa' => 100000, 'akhirPekan' => 125000],
        ];

        $jamDasar = [
            'Regular 2D' => ['12:30', '15:10', '17:50', '20:30'],
            'Regular 3D' => ['13:20', '16:00', '18:40', '21:20'],
            'IMAX' => ['12:00', '15:00', '18:00', '21:00'],
            'Premiere 2D' => ['13:00', '16:20', '19:40'],
        ];

        // Tidak semua film tayang di semua format. Aturan sederhana untuk data contoh:
        // IMAX dan 3D hanya untuk genre yang layar besarnya terasa.
        $format = ['Regular 2D'];

        if (in_array($film['genre'], ['Laga', 'Fiksi Ilmiah', 'Petualangan', 'Horor'])) {
            $format[] = 'Regular 3D';
            $format[] = 'IMAX';
        }

        $format[] = 'Premiere 2D';

        $jadwal = [];
        foreach ($format as $layar) {
            foreach ($jamDasar[$layar] as $jam) {
                $jadwal[$layar][] = $tambahMenit($jam, $geser);
            }
        }

        $adaPoster = file_exists(public_path('img/' . $film['poster']));
    @endphp

    <p class="border-b border-nema-line/40 bg-nema-surface px-4 py-3 text-center text-sm text-nema-muted sm:px-6">
        Data film di halaman ini masih contoh, belum tersambung ke database.
    </p>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-10">

        <a href="{{ url('/') }}"
           class="inline-flex min-h-11 items-center text-sm text-nema-muted transition-colors hover:text-nema-text">
            &larr;&nbsp; Kembali ke beranda
        </a>

        <div class="mt-4 grid gap-10 lg:grid-cols-[320px_1fr] lg:gap-16">

            {{-- Kolom kiri menempel saat halaman digulir, jadi poster dan judul selalu
                 terlihat sementara jadwalnya digulir di sebelah kanan. --}}
            <div class="lg:sticky lg:top-24 lg:self-start">

                <div class="flex gap-6 sm:gap-8 lg:block">

                    <div class="w-32 shrink-0 sm:w-40 lg:w-full">
                        @if ($adaPoster)
                            <img src="{{ asset('img/' . $film['poster']) }}" alt="Poster film {{ $film['judul'] }}"
                                 class="aspect-2/3 w-full rounded-xl object-cover">
                        @else
                            <div class="flex aspect-2/3 w-full items-end rounded-xl bg-nema-surface-2 p-4">
                                <span class="text-xs text-nema-muted">Poster belum tersedia</span>
                            </div>
                        @endif
                    </div>

                    <div class="min-w-0 lg:mt-6">
                        <h1 class="text-2xl leading-tight sm:text-3xl">{{ $film['judul'] }}</h1>

                        <p class="mt-2 text-nema-muted">{{ $film['tagline'] }}</p>

                        <div class="mt-4 flex flex-wrap items-center gap-2 text-sm">
                            <span class="rounded-md bg-nema-surface px-3 py-1.5">{{ $film['genre'] }}</span>
                            <span class="rounded-md bg-nema-surface px-3 py-1.5">{{ $film['durasi'] }} menit</span>
                            <span class="rounded-md bg-nema-maroon px-3 py-1.5 font-medium text-white">{{ $film['usia'] }}</span>
                        </div>

                    </div>

                </div>
            </div>

            <div class="min-w-0">

                @if ($film['mulai'])

                    <div class="rounded-xl border border-nema-line bg-nema-surface p-6 sm:p-8">
                        <h2 class="text-xl sm:text-2xl">Belum tayang</h2>
                        <p class="mt-3 max-w-prose text-nema-muted">
                            Film ini mulai tayang {{ $tanggalIndo($film['mulai']) }}.
                            Jadwal dan pemesanan kursi dibuka mendekati tanggal itu.
                        </p>
                    </div>

                @else

                    <div class="no-scrollbar mt-6 flex gap-2 overflow-x-auto pb-2">
                        @foreach ($daftarTanggal as $t)
                            @php $aktif = $t->format('Y-m-d') === $tanggal->format('Y-m-d'); @endphp

                            <a href="{{ url('/film/' . $film['slug']) }}?tanggal={{ $t->format('Y-m-d') }}"
                               @if ($aktif) aria-current="date" @endif
                               class="flex min-h-11 w-20 shrink-0 flex-col items-center justify-center rounded-lg py-2 {{ $aktif ? 'bg-nema-maroon text-white' : 'border border-nema-line text-nema-muted transition-colors hover:bg-nema-surface' }}">
                                <span class="text-xs">{{ $loop->first ? 'Hari ini' : $namaHari[$t->dayOfWeek] }}</span>
                                <span class="text-lg font-semibold">{{ $t->format('j') }}</span>
                            </a>
                        @endforeach
                    </div>

                    <div class="mt-4 divide-y divide-nema-line/40 border-y border-nema-line/40">
                        @foreach ($jadwal as $layar => $jamList)
                            <div class="py-5">

                                <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                                    <h3 class="text-base">{{ $layar }}</h3>
                                    <p class="text-sm text-nema-muted">
                                        Rp {{ number_format($daftarHarga[$layar][$akhirPekan ? 'akhirPekan' : 'biasa'], 0, ',', '.') }}
                                    </p>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($jamList as $jam)
                                        <a href="{{ url('/masuk') }}"
                                           class="inline-flex min-h-11 min-w-20 items-center justify-center rounded-md border border-nema-line px-4 transition-colors hover:bg-nema-surface">
                                            {{ $jam }}
                                        </a>
                                    @endforeach
                                </div>

                            </div>
                        @endforeach
                    </div>

                @endif

                <div class="mt-12 border-t border-nema-line/40 pt-6">
                    <h2 class="text-xl sm:text-2xl">Sinopsis</h2>
                    <p class="mt-3 max-w-prose text-nema-muted">{{ $film['sinopsis'] }}</p>
                </div>

            </div>

        </div>
    </div>

@endsection
