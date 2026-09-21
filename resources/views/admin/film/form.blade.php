@extends('layouts.app')

@section('judul', ($movie->exists ? 'Ubah' : 'Tambah') . ' Film, AoraNema')

@section('konten')

    @include('admin.nav')

    <div class="mx-auto max-w-2xl px-4 py-8 sm:px-6">

        <a href="{{ url('/admin/film') }}"
            class="inline-flex min-h-11 items-center text-sm text-nema-muted transition-colors hover:text-nema-text">
            &larr;&nbsp; Kembali ke daftar film
        </a>

        <h1 class="mt-4 text-2xl sm:text-3xl">{{ $movie->exists ? 'Ubah Film' : 'Tambah Film' }}</h1>

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

        <form method="post" action="{{ $movie->exists ? url('/admin/film/' . $movie->id) : url('/admin/film') }}"
            class="mt-8 space-y-6">
            @csrf
            @if ($movie->exists)
                @method('put')
            @endif

            <div>
                <label for="title" class="block text-sm">Judul</label>
                <input type="text" id="title" name="title" required maxlength="255"
                    value="{{ old('title', $movie->title) }}"
                    class="mt-2 block min-h-11 w-full rounded-md border border-nema-line bg-nema-surface px-4">
            </div>

            <div>
                <label for="synopsis" class="block text-sm">Sinopsis</label>
                <textarea id="synopsis" name="synopsis" rows="4"
                    class="mt-2 block w-full rounded-md border border-nema-line bg-nema-surface px-4 py-3">{{ old('synopsis', $movie->synopsis) }}</textarea>
            </div>

            <div>
                <label for="poster_url" class="block text-sm">Alamat poster</label>
                <input type="text" id="poster_url" name="poster_url" maxlength="255"
                    value="{{ old('poster_url', $movie->poster_url) }}"
                    class="mt-2 block min-h-11 w-full rounded-md border border-nema-line bg-nema-surface px-4">
                <p class="mt-2 text-xs text-nema-muted">
                    Boleh alamat lengkap seperti <code>https://...jpg</code>, atau nama berkas yang ada di
                    <code>public/img/</code>.
                </p>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="duration_minutes" class="block text-sm">Durasi (menit)</label>
                    <input type="number" id="duration_minutes" name="duration_minutes" min="1" max="600"
                        value="{{ old('duration_minutes', $movie->duration_minutes) }}"
                        class="mt-2 block min-h-11 w-full rounded-md border border-nema-line bg-nema-surface px-4">
                </div>

                <div>
                    <label for="release_date" class="block text-sm">Tanggal rilis</label>
                    <input type="date" id="release_date" name="release_date"
                        value="{{ old('release_date', $movie->release_date ? \Illuminate\Support\Carbon::parse($movie->release_date)->format('Y-m-d') : '') }}"
                        class="mt-2 block min-h-11 w-full rounded-md border border-nema-line bg-nema-surface px-4">
                </div>
            </div>

            <fieldset>
                <legend class="text-sm">Genre</legend>

                @if ($genre->isEmpty())
                    <p class="mt-2 text-sm text-nema-muted">
                        Belum ada genre di database. Genre terisi lewat seeder TMDB.
                    </p>
                @else
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($genre as $g)
                            <label
                                class="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-md border border-nema-line px-4 transition-colors hover:bg-nema-surface has-checked:border-nema-accent has-checked:bg-nema-surface">
                                <input type="checkbox" name="genre[]" value="{{ $g->id }}"
                                    @checked(in_array($g->id, old('genre', $movie->genres->pluck('id')->all()))) class="size-4 accent-nema-maroon">
                                {{ $g->name }}
                            </label>
                        @endforeach
                    </div>
                @endif
            </fieldset>

            <label class="inline-flex min-h-11 cursor-pointer items-center gap-3">
                <input type="checkbox" name="is_showing" value="1" @checked(old('is_showing', $movie->exists ? $movie->is_showing : true))
                    class="size-5 accent-nema-maroon">
                <span>Sedang tayang</span>
            </label>

            <div class="flex flex-wrap gap-3 border-t border-nema-line/40 pt-6">
                <button type="submit"
                    class="inline-flex min-h-11 items-center rounded-md bg-nema-maroon px-6 font-medium text-white transition-colors hover:bg-nema-maroon-hover">
                    {{ $movie->exists ? 'Simpan perubahan' : 'Tambah film' }}
                </button>

                <a href="{{ url('/admin/film') }}"
                    class="inline-flex min-h-11 items-center rounded-md border border-nema-line px-6 transition-colors hover:bg-nema-surface">
                    Batal
                </a>
            </div>
        </form>

    </div>

@endsection
