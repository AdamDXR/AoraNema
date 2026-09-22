<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MovieController extends Controller
{
    public function index(Request $request)
    {
        $cari = trim((string) $request->query('cari'));
        $genre = $request->query('genre');
        $status = in_array($request->query('status'), ['tayang', 'segera']) ? $request->query('status') : 'semua';
        $urut = in_array($request->query('urut'), ['az', 'za']) ? $request->query('urut') : 'terbaru';
        $tampilan = $request->query('tampilan') === 'baris' ? 'baris' : 'kotak';

        $film = Movie::with('genres')
            ->when($status === 'tayang', function ($query) {
                $query->where('is_showing', true);
            })
            ->when($status === 'segera', function ($query) {
                // Anggap film yang is_showing = false sebagai 'Segera Tayang'
                $query->where('is_showing', false);
            })
            ->when($genre, function ($query, $genre) {
                $query->whereHas('genres', function ($q) use ($genre) {
                    $q->where('name', $genre);
                });
            })
            ->when($cari !== '', function ($query) use ($cari) {
                $query->where('title', 'like', '%' . $cari . '%');
            })
            ->get()
            ->map(fn ($movie) => $movie->kartu());

        // "Terbaru" menaruh film yang sedang tayang di atas, rilis paling baru dulu, lalu film yang
        // akan tayang, tanggal paling dekat dulu. Tanpa pemisahan ini, film yang belum tayang
        // selalu naik ke puncak karena tanggal rilisnya di masa depan.
        $film = match ($urut) {
            'az' => $film->sortBy('judul', SORT_NATURAL | SORT_FLAG_CASE),
            'za' => $film->sortByDesc('judul', SORT_NATURAL | SORT_FLAG_CASE),
            default => $film->where('tayang', true)->sortByDesc('rilis')
                ->concat($film->where('tayang', false)->sortBy('rilis')),
        };

        $film = $film->values()->all();

        return view('daftar-film', compact('film', 'cari', 'status', 'urut', 'tampilan'));
    }

    public function show(Request $request, string $slug)
    {
        // 1. Ekstrak ID dari URL (Misal: 'coyote-vs-acme-3' -> kita ambil angka 3)
        $parts = explode('-', $slug);
        $id = end($parts);

        // 2. Cari film di database. fail() akan otomatis menampilkan halaman 404 jika ID tidak ada.
        $movie = Movie::with('genres')->findOrFail($id);

        // 3. Format data persis seperti yang diharapkan oleh film.blade.php
        $film = $movie->kartu() + [
            'slug' => $slug,
            'sinopsis' => $movie->synopsis ?? 'Sinopsis belum tersedia.',
            // Film yang belum tayang menampilkan tanggal rilisnya, bukan jadwal.
            'mulai' => $movie->is_showing ? null : $movie->release_date,
        ];

        // 4. Tanggal dibatasi ke enam hari yang ditampilkan di halaman, supaya parameter
        // di URL tidak bisa dipakai meminta jadwal sembarang tanggal.
        $hariIni = now()->startOfDay();
        $tanggal = $hariIni->copy();

        for ($i = 1; $i < 6; $i++) {
            if ($hariIni->copy()->addDays($i)->format('Y-m-d') === $request->query('tanggal')) {
                $tanggal = $hariIni->copy()->addDays($i);
            }
        }

        // 5. Jadwal tayang dari tabel showtimes pada tanggal itu, dikelompokkan per studio.
        // Jam yang sudah lewat tidak ditampilkan karena sudah tidak bisa dipesan.
        $jadwal = $movie->showtimes()
            ->with('studio')
            ->whereBetween('show_time', [$tanggal->copy()->max(now()), $tanggal->copy()->endOfDay()])
            ->orderBy('show_time')
            ->get()
            ->groupBy(fn ($s) => $s->studio->name);

                return view('film', compact('film', 'tanggal', 'jadwal'));
    }
}