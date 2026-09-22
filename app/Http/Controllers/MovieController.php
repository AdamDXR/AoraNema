<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MovieController extends Controller
{
    public function index(Request $request)
    {
        $cari = $request->query('cari');
        $genre = $request->query('genre');
        $status = $request->query('status', 'semua'); // Pilihan: tayang, segera, semua

        // Ambil daftar nama genre yang ada di database untuk menu dropdown
        $daftarGenre = \App\Models\Genre::orderBy('name')->pluck('name');

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
            ->when($cari, function ($query, $cari) {
                $query->where('title', 'like', '%' . $cari . '%');
            })
            ->get();

        return view('daftar-film', compact('film', 'daftarGenre', 'cari', 'genre', 'status'));
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