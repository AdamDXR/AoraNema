@extends('layouts.app')

@section('judul', 'Halaman tidak ditemukan, AoraNema')

@section('konten')

    <div class="mx-auto flex max-w-xl flex-col items-center px-4 py-20 text-center sm:px-6 sm:py-28">

        {{-- Angkanya dibuat besar dan redup sebagai latar, bukan sebagai judul, supaya
             yang pertama terbaca tetap kalimat penjelasnya. --}}
        <p class="text-7xl font-semibold text-nema-surface-2 sm:text-8xl" aria-hidden="true">404</p>

        <h1 class="mt-4 text-2xl sm:text-3xl">Halaman ini tidak ada</h1>

        <p class="mt-4 text-nema-muted">
            Mungkin alamatnya salah ketik, atau filmnya sudah tidak tayang lagi di AoraNema.
        </p>

        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ url('/') }}"
               class="inline-flex min-h-11 items-center rounded-md bg-nema-maroon px-6 font-medium text-white transition-colors hover:bg-nema-maroon-hover">
                Kembali ke beranda
            </a>

            <a href="{{ url('/film') }}"
               class="inline-flex min-h-11 items-center rounded-md border border-nema-line px-6 transition-colors hover:bg-nema-surface">
                Lihat semua film
            </a>
        </div>

    </div>

@endsection
