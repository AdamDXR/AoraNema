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
        $film = [
            'slug' => $slug,
            'judul' => $movie->title,
            'tagline' => 'Saksikan keseruannya di bioskop kesayangan Anda.',
            'sinopsis' => $movie->synopsis ?? 'Sinopsis belum tersedia.',
            'genre' => $movie->genres->pluck('name')->implode(', '),
            'durasi' => $movie->duration_minutes,
            'usia' => '13+',
            'poster' => $movie->poster_url, // URL TMDB asli
            'mulai' => null, // Kita anggap semua film TMDB ini sudah tayang
        ];

        // 4. Tangkap parameter '?tanggal=' dari URL. Jika kosong, gunakan hari ini.
        $tanggal = $request->query('tanggal') 
            ? Carbon::parse($request->query('tanggal'))->startOfDay() 
            : now()->startOfDay();

        // 5. Lempar data film dan objek tanggal ke view
        return view('film', compact('film', 'tanggal'));
    }
}