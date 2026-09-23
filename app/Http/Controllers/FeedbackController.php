<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Services\AoranemaMlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class FeedbackController extends Controller
{
    /**
     * Menampilkan form feedback untuk user.
     * Tampilan aslinya akan dikerjakan oleh tim FE.
     */
    public function create()
    {
        return view('feedback.create');
    }

    /**
     * Menyimpan feedback dari user, setelah diproses oleh ML Sentiment.
     */
    public function store(Request $request, AoranemaMlService $ml)
    {
        $validated = $request->validate([
            'feedbacks' => ['required', 'array', 'min:1'],
            'feedbacks.*.category' => [
                'required',
                'string',
                'in:booking,payment,application,customer_service,cinema_service,other',
            ],
            'feedbacks.*.comment' => [
                'required',
                'string',
                'min:1',
                'max:2000',
            ],
        ]);

        try {
            $analyzed = [];

            foreach ($validated['feedbacks'] as $item) {
                // Memanggil FastAPI endpoint /sentiment
                $prediction = $ml->analyzeSentiment($item['comment']);

                $analyzed[] = [
                    'category' => $item['category'],
                    'comment' => $item['comment'],
                    'sentiment' => $prediction['sentiment'],
                    'confidence' => $prediction['confidence'],
                ];
            }

            $saved = DB::transaction(function () use ($analyzed, $request) {
                $results = [];

                foreach ($analyzed as $item) {
                    $results[] = Feedback::create([
                        'user_id' => $request->user()->id,
                        'category' => $item['category'],
                        'comment' => $item['comment'],
                        'sentiment' => $item['sentiment'],
                        'confidence' => $item['confidence'],
                    ]);
                }

                return $results;
            });

            // Mengembalikan JSON response. Jika FE mau pakai Blade redirect, silakan disesuaikan di sisi FE.
            return response()->json([
                'message' => 'Feedback berhasil disimpan.',
                'data' => $saved,
            ], 201);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Feedback belum dapat diproses. Silakan coba kembali.',
                'error' => $e->getMessage()
            ], 503);
        }
    }

    /**
     * Menampilkan dashboard statistik sentimen untuk Admin.
     */
    public function indexAdmin()
    {
        $summary = Feedback::selectRaw('sentiment, COUNT(*) as total')
            ->groupBy('sentiment')
            ->pluck('total', 'sentiment')
            ->toArray();

        $byCategory = Feedback::selectRaw('category, sentiment, COUNT(*) as total')
            ->groupBy('category', 'sentiment')
            ->get();

        $latestFeedbacks = Feedback::with('user')
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        // Mengirim data mentah ke view admin. Tim FE akan menata tampilannya.
        return view('admin.feedback.index', compact('summary', 'byCategory', 'latestFeedbacks'));
    }
}
