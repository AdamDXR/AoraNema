<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Support\Facades\Auth;
use App\Services\MLRecommendationService;

class HomeController extends Controller
{
    public function index()
    {
        // 1. Ambil semua film dari database beserta genrenya (mencegah N+1 Query)
        $dbMovies = Movie::with(['genres', 'jadwalMendatang.studio'])->where('is_showing', true)->get();

        // 2. Ubah/Map objek database menjadi format array statis yang dikenali oleh beranda.blade.php
        $semuaFilm = $dbMovies->map(function (\App\Models\Movie $movie) {
            // Isi kartu (judul, poster, durasi, tagline, pilihan pengelola, dan lainnya) diambil
            // dari Movie::kartu() supaya sama persis dengan kartu di halaman /film.
            return $movie->kartu() + [
                'sinopsis' => $movie->synopsis ?? 'Sinopsis belum tersedia.',

                // Semuanya dianggap sedang tayang (mulai = null)
                'mulai' => null,
                'id' => $movie->id,
            ];
        })->toArray();

        $rekomendasi = [];
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if ($user && $user->isUser()) {
            $mlService = new MLRecommendationService();
            // candidates: film yang sedang tayang
            $candidates = $dbMovies;
            // movieCatalog: semua film di DB untuk mencocokkan riwayat user
            $movieCatalog = Movie::with('genres')->get();

            $recommendedIds = $mlService->getRecommendationsForUser($user, $candidates, $movieCatalog);

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