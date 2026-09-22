@extends('layouts.app')

@section('judul', 'Terjadi kesalahan, AoraNema')

@section('konten')

    <div class="mx-auto flex max-w-xl flex-col items-center px-4 py-20 text-center sm:px-6 sm:py-28">

        {{-- Angkanya dibuat besar dan redup sebagai latar, bukan sebagai judul, supaya
             yang pertama terbaca tetap kalimat penjelasnya. --}}
        <p class="text-7xl font-semibold text-nema-surface-2 sm:text-8xl" aria-hidden="true">500</p>

        <h1 class="mt-4 text-2xl sm:text-3xl">Ada yang tidak beres di sisi kami</h1>

        <p class="mt-4 text-nema-muted">
            Ini bukan kesalahanmu. Coba lagi beberapa saat lagi. Kalau kamu sedang memesan,
            cek Tiket Saya dulu supaya tidak memesan dua kali.
        </p>

        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ url('/') }}"
               class="inline-flex min-h-11 items-center rounded-md bg-nema-maroon px-6 font-medium text-white transition-colors hover:bg-nema-maroon-hover">
                Kembali ke beranda
            </a>
        </div>

    </div>

@endsection
