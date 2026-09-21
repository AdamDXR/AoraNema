@extends('layouts.app')

@section('judul', 'Semua Film, AoraNema')

@section('konten')

    <p class="border-b border-nema-line/40 bg-nema-surface px-4 py-3 text-center text-sm text-nema-muted sm:px-6">
        Film di halaman ini masih data contoh, belum tersambung ke database.
    </p>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-10">

        <h1 class="text-2xl sm:text-3xl">Semua Film</h1>

        {{-- Pencarian dan saringan dikirim lewat alamat, bukan JavaScript, supaya hasilnya
             bisa ditautkan ke orang lain dan tetap jalan tanpa skrip. --}}
        <form method="get" action="{{ url('/film') }}" class="mt-6 flex flex-wrap gap-3">
            <input type="hidden" name="genre" value="{{ $genre }}">
            <input type="hidden" name="status" value="{{ $status }}">

            <label for="cari" class="sr-only">Cari judul film</label>
            <input type="search" id="cari" name="cari" value="{{ $cari }}"
                   placeholder="Cari judul film"
                   class="min-h-11 w-full grow rounded-md border border-nema-line bg-nema-surface px-4 sm:w-auto">

            <button type="submit"
                    class="inline-flex min-h-11 items-center rounded-md bg-nema-maroon px-6 font-medium text-white transition-colors hover:bg-nema-maroon-hover">
                Cari
            </button>

            @if ($cari !== '' || $genre !== '' || $status !== 'semua')
                <a href="{{ url('/film') }}"
                   class="inline-flex min-h-11 items-center rounded-md border border-nema-line px-6 transition-colors hover:bg-nema-surface">
                    Bersihkan
                </a>
            @endif
        </form>

        <div class="mt-6 flex flex-wrap gap-2">
            @foreach (['semua' => 'Semua', 'tayang' => 'Sedang Tayang', 'segera' => 'Segera Tayang'] as $nilai => $label)
                <a href="{{ request()->fullUrlWithQuery(['status' => $nilai === 'semua' ? null : $nilai]) }}"
                   @if ($status === $nilai) aria-current="page" @endif
                   class="inline-flex min-h-11 items-center rounded-md px-4 text-sm transition-colors {{ $status === $nilai ? 'border border-nema-accent bg-nema-maroon text-white' : 'border border-nema-line text-nema-muted hover:bg-nema-surface' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="mt-3 flex flex-wrap gap-2">
            <a href="{{ request()->fullUrlWithQuery(['genre' => null]) }}"
               @if ($genre === '') aria-current="page" @endif
               class="inline-flex min-h-11 items-center rounded-md px-4 text-sm transition-colors {{ $genre === '' ? 'border border-nema-accent bg-nema-surface' : 'border border-nema-line text-nema-muted hover:bg-nema-surface' }}">
                Semua genre
            </a>

            @foreach ($daftarGenre as $g)
                <a href="{{ request()->fullUrlWithQuery(['genre' => $g]) }}"
                   @if ($genre === $g) aria-current="page" @endif
                   class="inline-flex min-h-11 items-center rounded-md px-4 text-sm transition-colors {{ $genre === $g ? 'border border-nema-accent bg-nema-surface' : 'border border-nema-line text-nema-muted hover:bg-nema-surface' }}">
                    {{ $g }}
                </a>
            @endforeach
        </div>

        <p class="mt-6 text-sm text-nema-muted" aria-live="polite">
            {{ count($film) }} film ditemukan{{ $cari !== '' ? ' untuk pencarian "' . $cari . '"' : '' }}.
        </p>

        @if (count($film))
            <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-5 lg:grid-cols-5">
                @foreach ($film as $f)
                    <article class="group relative">
                        <a href="{{ url('/film/' . $f['slug']) }}" class="block">
                            <div class="relative overflow-hidden rounded-lg bg-nema-surface-2">
                                @if (file_exists(public_path('img/' . $f['poster'])))
                                    <img src="{{ asset('img/' . $f['poster']) }}"
                                         alt="Poster film {{ $f['judul'] }}"
                                         class="aspect-2/3 w-full object-cover transition-transform duration-300 group-hover:scale-105">

                                    {{-- Kabut gelap dari dasar, supaya judul tetap terbaca di atas poster seterang apa pun. --}}
                                    <div class="absolute inset-x-0 bottom-0 h-1/2 bg-linear-to-t from-black/95 via-black/70 to-transparent"></div>

                                    <h2 class="absolute inset-x-0 bottom-0 p-3 text-base leading-tight text-white">
                                        {{ $f['judul'] }}
                                    </h2>
                                @else
                                    <div class="flex aspect-2/3 flex-col justify-end gap-2 p-3">
                                        <span class="text-xs text-nema-muted">Poster belum tersedia</span>
                                        <h2 class="text-base leading-tight">{{ $f['judul'] }}</h2>
                                    </div>
                                @endif
                            </div>
                        </a>

                        <p class="mt-2 text-xs text-nema-muted">
                            {{ $f['genre'] }} &middot; {{ $f['usia'] }}
                            @if ($f['mulai'])
                                &middot; segera
                            @endif
                        </p>
                    </article>
                @endforeach
            </div>
        @else
            <div class="mt-6 rounded-xl border border-nema-line bg-nema-surface p-8 text-center">
                <p>Tidak ada film yang cocok.</p>
                <p class="mt-2 text-sm text-nema-muted">
                    Coba kata kunci lain, atau hapus saringan genre dan statusnya.
                </p>
                <a href="{{ url('/film') }}"
                   class="mt-6 inline-flex min-h-11 items-center rounded-md border border-nema-line px-6 transition-colors hover:bg-nema-surface-2">
                    Tampilkan semua film
                </a>
            </div>
        @endif

    </div>

@endsection
