@extends('layouts.app')

@section('judul', ($jadwal->exists ? 'Ubah' : 'Tambah') . ' Jadwal, AoraNema')

@section('konten')

    @include('admin.nav')

    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6">

        <a href="{{ url('/admin/jadwal') }}"
           class="inline-flex min-h-11 items-center text-sm text-nema-muted transition-colors hover:text-nema-text">
            &larr;&nbsp; Kembali ke daftar jadwal
        </a>

        <h1 class="mt-4 text-2xl sm:text-3xl">{{ $jadwal->exists ? 'Ubah Jadwal' : 'Tambah Jadwal' }}</h1>

        @if ($errors->any())
            <div class="mt-6 rounded-lg border border-nema-accent bg-nema-surface p-4">
                <p class="text-sm">Ada {{ $errors->count() }} isian yang perlu dibetulkan:</p>
                <ul class="mt-2 list-inside list-disc text-sm text-nema-muted">
                    @foreach ($errors->all() as $pesan)
                        <li>{{ $pesan }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('gagal'))
            <p role="alert" class="mt-6 rounded-lg border border-nema-accent bg-nema-surface p-4 text-sm">
                {{ session('gagal') }}
            </p>
        @endif

        <form method="post"
              action="{{ $jadwal->exists ? url('/admin/jadwal/' . $jadwal->id) : url('/admin/jadwal') }}"
              class="mt-8 space-y-6">
            @csrf
            @if ($jadwal->exists)
                @method('put')
            @endif

            <div>
                <label for="movie_id" class="block text-sm">Film</label>
                <select id="movie_id" name="movie_id" required
                        class="mt-2 block min-h-11 w-full rounded-md border border-nema-line bg-nema-surface px-4">
                    <option value="">Pilih film</option>
                    @foreach ($film as $f)
                        <option value="{{ $f->id }}" @selected(old('movie_id', $jadwal->movie_id) == $f->id)>
                            {{ $f->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="studio_id" class="block text-sm">Studio</label>
                <select id="studio_id" name="studio_id" required
                        class="mt-2 block min-h-11 w-full rounded-md border border-nema-line bg-nema-surface px-4">
                    <option value="">Pilih studio</option>
                    @foreach ($studio as $s)
                        <option value="{{ $s->id }}" @selected(old('studio_id', $jadwal->studio_id) == $s->id)>
                            {{ $s->name }} ({{ $s->capacity }} kursi)
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <div>
                    <label for="show_time" class="block text-sm">Waktu tayang</label>
                    <input type="datetime-local" id="show_time" name="show_time" required
                           value="{{ old('show_time', $jadwal->show_time ? \Illuminate\Support\Carbon::parse($jadwal->show_time)->format('Y-m-d\TH:i') : '') }}"
                           class="mt-2 block min-h-11 w-full rounded-md border border-nema-line bg-nema-surface px-4">
                </div>

            </div>

            <p class="text-sm text-nema-muted">
                Harga per kursi diambil otomatis dari tarif studio: hari biasa, atau akhir pekan
                untuk Jumat sampai Minggu. Tarifnya diubah di halaman Studio.
            </p>

            <p class="text-sm text-nema-muted">
                Satu studio tidak bisa memutar dua film yang waktunya bertabrakan, termasuk jeda
                15 menit di antaranya. Kalau bentrok, simpanannya ditolak dan kamu diberi tahu
                jadwal mana yang bertabrakan.
            </p>

            <div class="flex flex-wrap gap-3 border-t border-nema-line/40 pt-6">
                <button type="submit"
                        class="inline-flex min-h-11 items-center rounded-md bg-nema-maroon px-6 font-medium text-white transition-colors hover:bg-nema-maroon-hover">
                    {{ $jadwal->exists ? 'Simpan perubahan' : 'Tambah jadwal' }}
                </button>

                <a href="{{ url('/admin/jadwal') }}"
                   class="inline-flex min-h-11 items-center rounded-md border border-nema-line px-6 transition-colors hover:bg-nema-surface">
                    Batal
                </a>
            </div>
        </form>

    </div>

@endsection
