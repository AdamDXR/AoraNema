<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Services\MLRecommendationService;

class HomeController extends Controller
{
    public function index()
    {
        // 1. Ambil semua film dari database beserta genrenya (mencegah N+1 Query)
        $dbMovies = Movie::with('genres')->where('is_showing', true)->get();

        // 2. Ubah/Map objek database menjadi format array statis yang dikenali oleh beranda.blade.php
        $semuaFilm = $dbMovies->map(function ($movie, $index) {
            return [
                // Menggabungkan slug judul dengan ID agar unik
                'slug' => Str::slug($movie->title) . '-' . $movie->id,
                'judul' => $movie->title,
                'tagline' => 'Saksikan keseruannya di bioskop kesayangan Anda.', 
                'sinopsis' => $movie->synopsis ?? 'Sinopsis belum tersedia.',
                // Menggabungkan array nama genre menjadi satu string teks dipisah koma
                'genre' => $movie->genres->pluck('name')->implode(', '),
                'durasi' => $movie->duration_minutes,
                'usia' => '13+', // Nilai default sementara
                'poster' => $movie->poster_url, // Langsung menggunakan link URL dari TMDB
                'rilis' => $movie->release_date,
                
                // Logika Buatan: Jadikan 3 film pertama sebagai 'Pilihan Pengelola' ($kurasi)
                'pilihan' => $index < 3 ? true : false,
                
                // Semuanya dianggap sedang tayang (mulai = null)
                'mulai' => null,
                'id' => $movie->id,
            ];
        })->toArray();

        $rekomendasi = [];
        if (Auth::check() && Auth::user()->isUser()) {
            $mlService = new MLRecommendationService();
            // candidates: film yang sedang tayang
            $candidates = $dbMovies;
            // movieCatalog: semua film di DB untuk mencocokkan riwayat user
            $movieCatalog = Movie::with('genres')->get();

            $recommendedIds = $mlService->getRecommendationsForUser(Auth::user(), $candidates, $movieCatalog);

            if (!empty($recommendedIds)) {
                $semuaFilmCollection = collect($semuaFilm);
                foreach ($recommendedIds as $id) {
                    $film = $semuaFilmCollection->firstWhere('id', $id);
                    if ($film) {
                        $rekomendasi[] = $film;
                    }
                }
                
                // Ambil 5 teratas saja
                $rekomendasi = array_slice($rekomendasi, 0, 5);
            }
        }

        // 3. Lempar variabel $semuaFilm ke view
        return view('beranda', compact('semuaFilm', 'rekomendasi'));
    }
}