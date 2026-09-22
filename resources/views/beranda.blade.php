@extends('layouts.app')

@section('judul', 'AoraNema, pesan tiket bioskop')

@section('konten')

    @php
        // Film yang belum tayang dipisahkan supaya tidak ikut muncul di bagian lain.
        // Penandanya kolom 'mulai', nanti datang dari movies.is_showing.
        $akanTayang = array_values(array_filter($semuaFilm, fn($f) => $f['mulai'] !== null));
        $film = array_values(array_filter($semuaFilm, fn($f) => $f['mulai'] === null));

        $unggulan = array_slice($film, 0, 3);

        // Urutan di dalam $kurasi adalah urutan pilihan pengelola, jadi yang pertama dapat ruang paling besar.
        $kurasi = array_values(array_filter($film, fn($f) => $f['pilihan']));
        $sorotan = $kurasi[0] ?? null;
        $pendamping = array_slice($kurasi, 1);

        // Film kurasi sudah tampil di bagian atas, jadi tidak diulang lagi di daftar rilis terbaru.
        $baru = array_values(array_filter($film, fn($f) => !$f['pilihan']));
        usort($baru, fn($a, $b) => strcmp($b['rilis'], $a['rilis']));
        $baru = array_slice($baru, 0, 4);

        // Nama bulan ditulis manual supaya tidak ikut berubah kalau locale aplikasi diganti.
        $tanggalIndo = function (string $iso) {
            $bulan = [
                1 => 'Januari',
                'Februari',
                'Maret',
                'April',
                'Mei',
                'Juni',
                'Juli',
                'Agustus',
                'September',
                'Oktober',
                'November',
                'Desember',
            ];
            [$tahun, $bln, $hari] = explode('-', $iso);

            return (int) $hari . ' ' . $bulan[(int) $bln] . ' ' . $tahun;
        };
    @endphp


    <section>

        <div id="hero" tabindex="0" class="no-scrollbar flex snap-x snap-mandatory overflow-x-auto scroll-smooth">
            @foreach ($unggulan as $f)
                <article class="w-full shrink-0 snap-start">
                    <div
                        class="mx-auto grid max-w-7xl items-center gap-10 px-4 py-12 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:py-20">

                        <div>
                            <p class="text-sm text-nema-accent">Sedang tayang</p>

                            <h1 class="mt-3 text-4xl leading-[1.05] sm:text-5xl lg:text-6xl"><a href="{{ url('/film/' . $f['slug']) }}" class="transition-colors hover:text-nema-accent">{{ $f['judul'] }}</a></h1>

                            <p class="mt-5 text-lg sm:text-xl">{{ $f['tagline'] }}</p>

                            <p class="mt-5 max-w-prose text-nema-muted">{{ $f['sinopsis'] }}</p>

                            <p class="mt-6 text-sm text-nema-muted">
                                {{ $f['genre'] }} &middot; {{ $f['durasi'] }} menit &middot; {{ $f['usia'] }}
                            </p>

                            <div class="mt-8 flex flex-wrap gap-3">
                                <a href="{{ url('/film/' . $f['slug']) }}"
                                    class="inline-flex min-h-11 items-center rounded-md bg-nema-maroon px-6 font-medium text-white transition-colors hover:bg-nema-maroon-hover">
                                    Pesan Tiket
                                </a>
                                <a href="#semua-film"
                                    class="inline-flex min-h-11 items-center rounded-md border border-nema-line px-6 transition-colors hover:bg-nema-surface">
                                    Lihat Film Lain
                                </a>
                            </div>
                        </div>

                        <a href="{{ url('/film/' . $f['slug']) }}"
                           class="group block w-full max-w-xs lg:ml-auto lg:max-w-sm">
                            @if ($f['poster'])
                                <img src="{{ $f['poster'] }}" alt="Poster film {{ $f['judul'] }}"
                                    class="aspect-2/3 w-full rounded-xl object-cover transition-transform duration-300 group-hover:scale-[1.02]">
                            @else
                                <div class="flex aspect-2/3 w-full items-end rounded-xl bg-nema-surface-2 p-5">
                                    <span class="text-xs text-nema-muted">Poster belum tersedia</span>
                                </div>
                            @endif
                        </a>

                    </div>
                </article>
            @endforeach
        </div>

    </section>

    {{-- Tombol "Lihat Film Lain" di hero menuju ke sini, jadi id-nya dipasang di pembungkus yang selalu ada. --}}
    <div id="semua-film" class="scroll-mt-20">

        @if ($sorotan)
            <section class="mx-auto max-w-7xl px-4 pt-10 sm:px-6 sm:pt-14">

                <div class="border-t border-nema-line/40 pt-6">
                    <h2 class="text-2xl sm:text-3xl">Dipilih Pengelola</h2>
                    <p class="mt-2 max-w-prose text-sm text-nema-muted">
                        {{ count($kurasi) }} film yang dipilih sendiri oleh pengelola bioskop untuk pekan ini. Yang paling
                        atas jadi pilihan utamanya, dan itu keputusan orang, bukan hasil hitungan.
                    </p>
                </div>

                {{-- Di ponsel susunannya jadi satu kolom: film utama dulu, baru dua pilihan berikutnya. --}}
                <div class="mt-8 grid gap-10 lg:grid-cols-12 lg:gap-12">

                    <article class="lg:col-span-8">
                        <div class="flex flex-col gap-6 sm:flex-row sm:gap-8">

                            <a href="{{ url('/film/' . $sorotan['slug']) }}"
                                class="group block w-full max-w-xs shrink-0 sm:w-56 sm:max-w-none lg:w-72">
                                @if ($sorotan['poster'])
                                    <img src="{{ $sorotan['poster'] }}"
                                        alt="Poster film {{ $sorotan['judul'] }}"
                                        class="aspect-2/3 w-full rounded-xl object-cover transition-transform duration-300 group-hover:scale-[1.02]">
                                @else
                                    <div class="flex aspect-2/3 w-full items-end rounded-xl bg-nema-surface-2 p-5">
                                        <span class="text-xs text-nema-muted">Poster belum tersedia</span>
                                    </div>
                                @endif
                            </a>

                            <div class="min-w-0">
                                <h3 class="text-3xl leading-tight sm:text-4xl"><a href="{{ url('/film/' . $sorotan['slug']) }}" class="transition-colors hover:text-nema-accent">{{ $sorotan['judul'] }}</a></h3>

                                <p class="mt-3 text-lg text-nema-accent">{{ $sorotan['tagline'] }}</p>

                                <p class="mt-4 max-w-prose text-nema-muted">{{ $sorotan['sinopsis'] }}</p>

                                <p class="mt-5 text-sm text-nema-muted">
                                    {{ $sorotan['genre'] }} &middot; {{ $sorotan['durasi'] }} menit &middot;
                                    {{ $sorotan['usia'] }} &middot; tayang sejak {{ $tanggalIndo($sorotan['rilis']) }}
                                </p>

                                <a href="{{ url('/film/' . $sorotan['slug']) }}"
                                    class="mt-7 inline-flex min-h-11 items-center rounded-md bg-nema-maroon px-6 font-medium text-white transition-colors hover:bg-nema-maroon-hover">
                                    Pesan Tiket
                                </a>
                            </div>

                        </div>
                    </article>

                    <ul
                        class="grid gap-x-8 sm:grid-cols-2 lg:col-span-4 lg:grid-cols-1 lg:gap-x-0 lg:border-l lg:border-nema-line/40 lg:pl-8">
                        @foreach ($pendamping as $f)
                            <li class="relative flex gap-4 border-t border-nema-line/40 py-5 lg:first:border-t-0 lg:first:pt-0">

                                <div class="w-20 shrink-0 sm:w-24">
                                    @if ($f['poster'])
                                        <img src="{{ $f['poster'] }}" alt="Poster film {{ $f['judul'] }}"
                                            class="aspect-2/3 w-full rounded-lg object-cover">
                                    @else
                                        <div class="flex aspect-2/3 w-full items-end rounded-lg bg-nema-surface-2 p-2">
                                            <span class="text-[11px] leading-tight text-nema-muted">Poster belum
                                                tersedia</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="min-w-0">
                                    <h3 class="text-lg leading-tight"><a href="{{ url('/film/' . $f['slug']) }}" class="after:absolute after:inset-0 focus-visible:outline-none">{{ $f['judul'] }}</a></h3>
                                    <p class="mt-1 text-xs text-nema-muted">{{ $f['genre'] }} &middot;
                                        {{ $f['durasi'] }} menit &middot; {{ $f['usia'] }}</p>
                                    <p class="mt-2 text-sm text-nema-muted">{{ $f['tagline'] }}</p>
                                </div>

                            </li>
                        @endforeach
                    </ul>

                </div>

            </section>
        @endif

        @if (count($baru))
            <section class="mx-auto max-w-7xl px-4 pt-14 sm:px-6 sm:pt-20">

                <div class="border-t border-nema-line/40 pt-6">
                    <h2 class="text-2xl sm:text-3xl">Baru Rilis</h2>
                    <p class="mt-2 max-w-prose text-sm text-nema-muted">
                        Urut dari tanggal tayang perdana yang paling baru. Tanggalnya ditulis di tiap baris, jadi urutannya
                        bisa dicek sendiri.
                    </p>
                </div>

                <ol class="mt-6 lg:grid lg:grid-cols-2 lg:gap-x-12">
                    @foreach ($baru as $f)
                        <li class="relative flex items-center gap-4 border-t border-nema-line/40 py-4 sm:gap-6 sm:py-5">

                            <div class="min-w-0 flex-1">
                                <p class="text-xs text-nema-muted">{{ $tanggalIndo($f['rilis']) }}</p>
                                <h3 class="mt-1 text-lg leading-tight sm:text-xl"><a href="{{ url('/film/' . $f['slug']) }}" class="after:absolute after:inset-0 focus-visible:outline-none">{{ $f['judul'] }}</a></h3>
                                <p class="mt-1 text-xs text-nema-muted sm:text-sm">{{ $f['genre'] }} &middot;
                                    {{ $f['durasi'] }} menit &middot; {{ $f['usia'] }}</p>
                            </div>

                            <div class="w-20 shrink-0 sm:w-24">
                                @if ($f['poster'])
                                    <img src="{{ $f['poster'] }}" alt="Poster film {{ $f['judul'] }}"
                                        class="aspect-2/3 w-full rounded-lg object-cover">
                                @else
                                    <div class="flex aspect-2/3 w-full items-end rounded-lg bg-nema-surface-2 p-2">
                                        <span class="text-[11px] leading-tight text-nema-muted">Poster belum tersedia</span>
                                    </div>
                                @endif
                            </div>

                        </li>
                    @endforeach
                </ol>

            </section>
        @endif

        <section class="mx-auto max-w-7xl px-4 pt-14 sm:px-6 sm:pt-20">

            <div class="border-t border-nema-line/40 pt-6">
                <h2 class="text-2xl sm:text-3xl">Semua Film</h2>
                <p class="mt-2 max-w-prose text-sm text-nema-muted">
                    Daftar lengkap {{ count($film) }} film yang sedang tayang di AoraNema, tanpa urutan khusus.
                </p>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-5 lg:grid-cols-5">
                @forelse ($film as $f)
                    <article class="group relative">
                    <a href="{{ url('/film/' . $f['slug']) }}" class="block">
                        <div class="relative overflow-hidden rounded-lg bg-nema-surface-2">
                            @if ($f['poster'])
                                <img src="{{ $f['poster'] }}" alt="Poster film {{ $f['judul'] }}"
                                    class="aspect-2/3 w-full object-cover transition-transform duration-300 group-hover:scale-105">

                                {{-- Kabut gelap dari dasar, supaya judul tetap terbaca di atas poster seterang apa pun. --}}
                                <div
                                    class="absolute inset-x-0 bottom-0 h-1/2 bg-linear-to-t from-black/95 via-black/70 to-transparent">
                                </div>

                                <h3 class="absolute inset-x-0 bottom-0 p-3 text-base leading-tight text-white">
                                    {{ $f['judul'] }}</h3>
                            @else
                                <div class="flex aspect-2/3 flex-col justify-end gap-2 p-3">
                                    <span class="text-xs text-nema-muted">Poster belum tersedia</span>
                                    <h3 class="text-base leading-tight">{{ $f['judul'] }}</h3>
                                </div>
                            @endif
                        </div>
                    </a>

                        <p class="mt-2 text-xs text-nema-muted">{{ $f['genre'] }} &middot; {{ $f['usia'] }}</p>
                    </article>
                @empty
                    <p class="col-span-full rounded-lg border border-nema-line bg-nema-surface p-6 text-sm text-nema-muted">
                        Belum ada film yang bisa ditampilkan. Jadwal pekan ini belum dimasukkan pengelola.
                    </p>
                @endforelse
            </div>

        </section>

    </div>

    <section class="mx-auto max-w-7xl px-4 pt-14 sm:px-6 sm:pt-20">

        <div class="border-t border-nema-line/40 pt-6">
            <h2 class="text-2xl sm:text-3xl">Akan Tayang</h2>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 sm:gap-6">
            @forelse ($akanTayang as $f)
                <article class="group relative flex gap-4 rounded-xl bg-nema-surface p-4 sm:gap-5 sm:p-5">

                    <div class="w-24 shrink-0 sm:w-28 lg:w-32">
                        @if ($f['poster'])
                            <img src="{{ $f['poster'] }}" alt="Poster film {{ $f['judul'] }}"
                                class="aspect-2/3 w-full rounded-lg object-cover">
                        @else
                            <div class="flex aspect-2/3 w-full items-end rounded-lg bg-nema-surface-2 p-2">
                                <span class="text-xs text-nema-muted">Poster belum ada</span>
                            </div>
                        @endif
                    </div>

                    <div class="min-w-0">
                        <h3 class="text-lg leading-tight sm:text-xl"><a href="{{ url('/film/' . $f['slug']) }}" class="after:absolute after:inset-0 focus-visible:outline-none">{{ $f['judul'] }}</a></h3>

                        <p class="mt-2 text-sm text-nema-muted">
                            {{ $f['genre'] }} &middot; {{ $f['durasi'] }} menit &middot; {{ $f['usia'] }}
                        </p>

                        <p class="mt-3 text-sm">{{ $f['tagline'] }}</p>

                        <p class="mt-4 text-sm font-medium">
                            Tayang mulai {{ $tanggalIndo($f['mulai']) }}
                        </p>
                    </div>

                </article>
            @empty
                <p class="rounded-lg border border-nema-line bg-nema-surface p-6 text-sm text-nema-muted sm:col-span-2">
                    Belum ada jadwal film berikutnya.
                </p>
            @endforelse
        </div>

    </section>

    <script>
        (function() {
            const hero = document.getElementById('hero');
            const kurangiGerak = window.matchMedia('(prefers-reduced-motion: reduce)');
            let jeda = false;

            function majuSatu() {
                if (jeda || kurangiGerak.matches) return;

                const batasAkhir = hero.scrollWidth - hero.clientWidth - 4;
                const tujuan = hero.scrollLeft >= batasAkhir ? 0 : hero.scrollLeft + hero.clientWidth;

                hero.scrollTo({
                    left: tujuan,
                    behavior: 'smooth'
                });
            }

            ['mouseenter', 'focusin', 'touchstart'].forEach(function(peristiwa) {
                hero.addEventListener(peristiwa, function() {
                    jeda = true;
                }, {
                    passive: true
                });
            });

            ['mouseleave', 'focusout', 'touchend'].forEach(function(peristiwa) {
                hero.addEventListener(peristiwa, function() {
                    jeda = false;
                }, {
                    passive: true
                });
            });

            setInterval(majuSatu, 6000);
        })();
    </script>

@endsection
