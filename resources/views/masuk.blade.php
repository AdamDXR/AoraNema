@extends('layouts.app')

@section('judul', 'Masuk, AoraNema')

@section('konten')
<section class="mx-auto max-w-md px-4 py-24 text-center sm:px-6">
    <h1 class="font-display text-3xl">Masuk</h1>

    <p class="mt-4 text-nema-muted">
        Halaman ini belum bisa dipakai. Sistem akun belum dibangun,
        jadi belum ada yang bisa masuk.
    </p>

    <a href="{{ url('/') }}"
       class="mt-8 inline-flex min-h-11 items-center rounded-md border border-nema-line px-5 transition-colors hover:bg-nema-surface">
        Kembali ke beranda
    </a>
</section>
@endsection
