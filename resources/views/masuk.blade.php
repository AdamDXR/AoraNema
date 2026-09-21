@extends('layouts.app')

@section('judul', 'Masuk, AoraNema')

@section('konten')
<section class="mx-auto max-w-md px-4 py-24 sm:px-6">
    <div class="text-center">
        <h1 class="font-display text-3xl">Masuk</h1>
        <p class="mt-2 text-sm text-nema-muted">Silakan masuk untuk memesan tiket.</p>
    </div>

    <form method="POST" action="{{ url('/masuk') }}" class="mt-8 space-y-5">
        @csrf

        {{-- Menampilkan pesan error jika login gagal --}}
        @if ($errors->any())
            <div class="rounded-md border border-red-500/30 bg-red-500/10 p-3 text-sm text-red-500">
                {{ $errors->first() }}
            </div>
        @endif

        <div>
            <label for="email" class="block text-sm font-medium text-nema-muted">Alamat Email</label>
            <input type="email" name="email" id="email" required value="{{ old('email') }}"
                class="mt-2 block w-full rounded-md border border-nema-line bg-nema-surface px-4 py-2.5 text-nema-text focus:border-nema-accent focus:outline-none focus:ring-1 focus:ring-nema-accent">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-nema-muted">Kata Sandi</label>
            <input type="password" name="password" id="password" required
                class="mt-2 block w-full rounded-md border border-nema-line bg-nema-surface px-4 py-2.5 text-nema-text focus:border-nema-accent focus:outline-none focus:ring-1 focus:ring-nema-accent">
        </div>

        <button type="submit"
            class="mt-2 inline-flex min-h-11 w-full items-center justify-center rounded-md bg-nema-maroon px-5 font-medium text-white transition-colors hover:bg-nema-maroon-hover">
            Masuk
        </button>
    </form>
</section>
@endsection