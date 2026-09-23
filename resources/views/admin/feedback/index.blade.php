@extends('layouts.app')

@section('judul', 'Masukan Penonton, AoraNema')

@section('konten')

    @include('admin.nav')

    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">

        @php
            $namaSentimen = ['positive' => 'Positif', 'neutral' => 'Netral', 'negative' => 'Negatif', 'unknown' => 'Belum dianalisis'];
            $namaKategori = [
                'booking' => 'Pemesanan tiket',
                'payment' => 'Pembayaran',
                'application' => 'Website AoraNema',
                'customer_service' => 'Layanan pelanggan',
                'cinema_service' => 'Layanan di bioskop',
                'other' => 'Lainnya',
            ];
            $total = array_sum($summary);
        @endphp

        <h1 class="text-2xl sm:text-3xl">Masukan Penonton</h1>

        <p class="mt-2 max-w-prose text-sm text-nema-muted">
            {{ $total }} masukan tersimpan. Nada tiap masukan (positif, netral, negatif) ditentukan model
            analisis sentimen, bukan dibaca satu per satu oleh pengelola.
        </p>

        @if ($total === 0)
            <div class="mt-8 rounded-xl bg-nema-surface px-6 py-12 text-center">
                <p class="text-lg">Belum ada masukan yang masuk</p>
                <p class="mt-2 text-sm text-nema-muted">
                    Masukan dari penonton muncul di sini setelah mereka mengirimnya lewat halaman Kirim Masukan.
                </p>
            </div>
        @else
            {{-- Ringkasan nada masukan. Angkanya dihitung dari seluruh masukan yang tersimpan. --}}
            <div class="mt-8 grid gap-4 sm:grid-cols-3">
                @foreach (array_slice($namaSentimen, 0, 3, true) as $kunci => $label)
                    @php $jumlah = $summary[$kunci] ?? 0; @endphp
                    <div class="rounded-xl bg-nema-surface p-5">
                        <p class="text-sm text-nema-muted">{{ $label }}</p>
                        <p class="mt-1 text-3xl font-semibold">{{ $jumlah }}</p>
                        <p class="mt-1 text-sm text-nema-muted">
                            {{ $total ? round($jumlah / $total * 100) : 0 }}% dari semua masukan
                        </p>
                    </div>
                    @endforeach

                @if (($summary['unknown'] ?? 0) > 0)
                    <div class="rounded-xl border border-nema-line bg-nema-surface p-5 sm:col-span-3">
                        <p class="text-sm">
                            {{ $summary['unknown'] }} masukan belum dianalisis nadanya, karena layanan
                            analisis sentimen tidak bisa dihubungi saat masukan itu dikirim.
                        </p>
                    </div>
                @endif
            </div>

            @php
                // Baris per kategori, kolomnya nada masukan. Kategori tanpa masukan tidak ditampilkan.
                $perKategori = $byCategory->groupBy('category');
            @endphp

            <h2 class="mt-12 text-xl sm:text-2xl">Per bagian layanan</h2>

            <div class="relative mt-4 overflow-x-auto">
                <table class="w-full min-w-2xl text-left text-sm">
                    <thead class="border-b border-nema-line/40 text-nema-muted">
                        <tr>
                            <th class="py-3 pr-4 font-normal">Bagian</th>
                            @foreach ($namaSentimen as $label)
                                <th class="py-3 pr-4 font-normal">{{ $label }}</th>
                            @endforeach
                            <th class="py-3 font-normal">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-nema-line/40">
                        @foreach ($perKategori as $kategori => $baris)
                            <tr>
                                <td class="py-4 pr-4">{{ $namaKategori[$kategori] ?? $kategori }}</td>
                                @foreach (array_keys($namaSentimen) as $kunci)
                                    <td class="py-4 pr-4 text-nema-muted">
                                        {{ $baris->firstWhere('sentiment', $kunci)->total ?? 0 }}
                                    </td>
                                @endforeach
                                <td class="py-4">{{ $baris->sum('total') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <h2 class="mt-12 text-xl sm:text-2xl">Masukan terbaru</h2>

            <ul class="mt-4 space-y-4">
                @foreach ($latestFeedbacks as $masukan)
                    <li class="rounded-xl bg-nema-surface p-5">
                        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <span class="font-medium">{{ $masukan->user?->name ?? 'Akun sudah dihapus' }}</span>
                            <span class="text-sm text-nema-muted">
                                {{ $namaKategori[$masukan->category] ?? $masukan->category }}
                                &middot; {{ $masukan->created_at->translatedFormat('d/m/Y H:i') }}
                            </span>

                            {{-- Nada masukan ditulis sebagai teks, bukan hanya warna, supaya tetap terbaca. --}}
                            <span class="ml-auto inline-flex min-h-7 items-center rounded-md bg-nema-surface-2 px-2.5 text-xs">
                                {{ $namaSentimen[$masukan->sentiment] ?? $masukan->sentiment }}
                                @if ($masukan->confidence)
                                    <span class="ml-1 text-nema-muted">{{ round($masukan->confidence * 100) }}% yakin</span>
                                @endif
                            </span>
                        </div>

                        <p class="mt-3 whitespace-pre-line">{{ $masukan->comment }}</p>
                    </li>
                @endforeach
            </ul>
        @endif

    </div>

@endsection
