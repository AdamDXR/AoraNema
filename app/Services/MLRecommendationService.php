<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MLRecommendationService
{
    /**
     * Dapatkan rekomendasi untuk user tertentu.
     * Mengembalikan daftar ID movie (movie_id) yang direkomendasikan secara berurutan.
     * Jika terjadi kegagalan (timeout/error), kembalikan array kosong.
     */
    public function getRecommendationsForUser(User $user, $candidates, $movieCatalog): array
    {
        $apiUrl = config('services.ml.url') . '/recommendations';

        // 1. Cek Riwayat Interaksi (Bookings = Implicit rating 5.0)
        // Ambil semua booking status paid, ambil movie_id
        $bookings = $user->bookings()->where('status', 'paid')->with('showtime')->get();
        $interactions = [];
        $favorite_movie_ids = [];

        foreach ($bookings as $booking) {
            if ($booking->showtime) {
                $movieId = $booking->showtime->movie_id;
                // Cegah duplikasi interaksi (bisa dirata-rata, tapi kita ambil 1 saja dengan rating 5.0)
                $interactions[$movieId] = [
                    'movie_id' => $movieId,
                    'rating' => 5.0
                ];
                $favorite_movie_ids[] = $movieId;
            }
        }
        
        $interactions = array_values($interactions);
        $favorite_movie_ids = array_unique($favorite_movie_ids);

        // 2. Tentukan Mode
        $mode = count($interactions) > 0 ? 'history' : 'onboarding';

        // 3. Susun Payload
        $payload = [
            'mode' => $mode,
            'movie_catalog' => $this->formatCatalog($movieCatalog),
            'candidates' => $this->formatCatalog($candidates),
            'top_k' => 10,
            'snapshot_year' => (int) date('Y'),
        ];

        if ($mode === 'history') {
            $payload['interactions'] = $interactions;
        } else {
            // Mode onboarding
            $favorite_genres = $user->genres()->pluck('name')->toArray();
            $payload['favorite_genres'] = $favorite_genres;
            $payload['favorite_movie_ids'] = $favorite_movie_ids; // biasanya kosong di onboarding
        }

        // 4. Kirim Request
        try {
            $response = Http::timeout(3)->post($apiUrl, $payload);
            
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['recommendations'])) {
                    // Extract movie_id dari recommendations list
                    return array_column($data['recommendations'], 'movie_id');
                }
            } else {
                Log::error('ML API Error: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('ML API Exception: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Format collection model Movie menjadi bentuk dict yang dikenali FastAPI.
     */
    private function formatCatalog($movies): array
    {
        $catalog = [];
        foreach ($movies as $movie) {
            $genres = $movie->genres->pluck('name')->implode('|');
            $releaseYear = $movie->release_date ? (int) date('Y', strtotime($movie->release_date)) : null;

            $item = [
                'movie_id' => $movie->id,
                'title' => $movie->title,
            ];

            if (!empty($genres)) {
                $item['genres'] = $genres;
            }
            if ($releaseYear) {
                $item['release_year'] = $releaseYear;
            }
            if ($movie->duration_minutes) {
                $item['runtime'] = $movie->duration_minutes;
            }
            if ($movie->tmdb_id) {
                $item['tmdb_id'] = $movie->tmdb_id;
            }

            $catalog[] = $item;
        }

        return $catalog;
    }
}
