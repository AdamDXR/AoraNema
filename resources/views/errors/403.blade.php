@extends('layouts.app')

@section('judul', 'Tidak bisa dibuka, AoraNema')

@section('konten')

    <div class="mx-auto flex max-w-xl flex-col items-center px-4 py-20 text-center sm:px-6 sm:py-28">

        {{-- Angkanya dibuat besar dan redup sebagai latar, bukan sebagai judul, supaya
             yang pertama terbaca tetap kalimat penjelasnya. --}}
        <p class="text-7xl font-semibold text-nema-surface-2 sm:text-8xl" aria-hidden="true">403</p>

        <h1 class="mt-4 text-2xl sm:text-3xl">Halaman ini bukan untuk akunmu</h1>

        {{-- Pesan dari abort(403, '...') ditulis untuk penonton, jadi ditampilkan kalau ada. --}}
        <p class="mt-4 text-nema-muted">
            {{ $exception->getMessage() ?: 'Akun yang sedang masuk tidak punya akses ke halaman ini.' }}
        </p>

        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ url('/') }}"
               class="inline-flex min-h-11 items-center rounded-md bg-nema-maroon px-6 font-medium text-white transition-colors hover:bg-nema-maroon-hover">
                Kembali ke beranda
            </a>
        </div>

    </div>

@endsection
