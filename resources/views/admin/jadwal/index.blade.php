@extends('layouts.app')

@section('judul', 'Kelola Jadwal Tayang, AoraNema')

@section('konten')

    @include('admin.nav')

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl">Kelola Jadwal Tayang</h1>
                <p class="mt-1 text-sm text-nema-muted">{{ $jadwal->total() }} jadwal tersimpan.</p>
            </div>

            @if ($bisaTambah)
                <a href="{{ url('/admin/jadwal/baru') }}"
                   class="inline-flex min-h-11 items-center rounded-md bg-nema-maroon px-5 font-medium text-white transition-colors hover:bg-nema-maroon-hover">
                    Tambah Jadwal
                </a>
            @endif
        </div>

        @if (session('sukses'))
            <p class="mt-6 rounded-lg border border-nema-accent bg-nema-surface px-4 py-3 text-sm">
                {{ session('sukses') }}
            </p>
        @endif

        @if (session('gagal'))
            <p class="mt-6 rounded-lg border border-nema-line bg-nema-surface px-4 py-3 text-sm">
                {{ session('gagal') }}
            </p>
        @endif

        @unless ($bisaTambah)
            <p class="mt-6 rounded-lg border border-nema-line bg-nema-surface p-4 text-sm text-nema-muted">
                Jadwal butuh minimal satu film dan satu studio.
                @if (! $adaFilm)
                    <a href="{{ url('/admin/film/baru') }}" class="text-nema-accent underline">Tambah film</a>.
                @endif
                @if (! $adaStudio)
                    <a href="{{ url('/admin/studio/baru') }}" class="text-nema-accent underline">Tambah studio</a>.
                @endif
            </p>
        @endunless

        <div class="mt-8 overflow-x-auto">
            <table class="w-full min-w-3xl text-left text-sm">
                <thead class="border-b border-nema-line/40 text-nema-muted">
                    <tr>
                        <th class="py-3 pr-4 font-normal">Waktu tayang</th>
                        <th class="py-3 pr-4 font-normal">Film</th>
                        <th class="py-3 pr-4 font-normal">Studio</th>
                        <th class="py-3 pr-4 font-normal">Harga</th>
                        <th class="py-3 pr-4 font-normal">Terisi</th>
                        <th class="py-3 font-normal"><span class="sr-only">Tindakan</span></th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-nema-line/40">
                    @forelse ($jadwal as $j)
                        <tr>
                            <td class="py-4 pr-4">
                                {{ \Illuminate\Support\Carbon::parse($j->show_time)->format('d/m/Y H:i') }}
                            </td>

                            <td class="py-4 pr-4">{{ $j->movie?->title ?? '—' }}</td>

                            <td class="py-4 pr-4 text-nema-muted">{{ $j->studio?->name ?? '—' }}</td>

                            <td class="py-4 pr-4 text-nema-muted">
                                Rp {{ number_format($j->price, 0, ',', '.') }}
                            </td>

                            <td class="py-4 pr-4 text-nema-muted">
                                {{ $j->bookings_count }} kursi
                            </td>

                            <td class="py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ url('/admin/jadwal/' . $j->id . '/ubah') }}"
                                       class="inline-flex min-h-11 items-center rounded-md border border-nema-line px-4 transition-colors hover:bg-nema-surface">
                                        Ubah
                                    </a>

                                    <form method="post" action="{{ url('/admin/jadwal/' . $j->id) }}"
                                          onsubmit="return confirm('Hapus jadwal ini?{{ $j->bookings_count ? ' Ada ' . $j->bookings_count . ' pesanan yang ikut terhapus.' : '' }}')">
                                        @csrf
                                        @method('delete')

                                        <button type="submit"
                                                class="inline-flex min-h-11 items-center rounded-md border border-nema-line px-4 transition-colors hover:bg-nema-surface">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-nema-muted">
                                Belum ada jadwal tayang.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-8">
            {{ $jadwal->links() }}
        </div>

    </div>

@endsection
