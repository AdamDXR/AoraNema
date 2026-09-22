<?php

namespace App\Http\Controllers;

use App\Models\Movie;

class HomeController extends Controller
{
    public function index()
    {
        // 1. Ambil semua film dari database beserta genrenya (mencegah N+1 Query)
        $dbMovies = Movie::with('genres')->where('is_showing', true)->get();

        // 2. Ubah/Map objek database menjadi format array statis yang dikenali oleh beranda.blade.php
        $semuaFilm = $dbMovies->map(function ($movie) {
            // Isi kartu (judul, poster, durasi, tagline, pilihan pengelola, dan lainnya) diambil
            // dari Movie::kartu() supaya sama persis dengan kartu di halaman /film.
            return $movie->kartu() + [
                'sinopsis' => $movie->synopsis ?? 'Sinopsis belum tersedia.',

                // Semuanya dianggap sedang tayang (mulai = null)
                'mulai' => null,
            ];
        })->toArray();

        // 3. Lempar variabel $semuaFilm ke view
        return view('beranda', compact('semuaFilm'));
    }
}