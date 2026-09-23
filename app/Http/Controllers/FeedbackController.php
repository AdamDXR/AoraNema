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

            $adaYangBelumDianalisis = false;

            foreach ($validated['feedbacks'] as $item) {
                try {
                    // Memanggil FastAPI endpoint /sentiment
                    $prediction = $ml->analyzeSentiment($item['comment']);
                    $nada = $prediction['sentiment'];
                    $keyakinan = $prediction['confidence'];
                } catch (Throwable $e) {
                    // Layanan sentimen sedang tidak bisa dihubungi. Masukannya tetap disimpan dengan
                    // nada 'unknown', bukan ditebak, supaya isi masukan penonton tidak hilang.
                    report($e);
                    $nada = 'unknown';
                    $keyakinan = null;
                    $adaYangBelumDianalisis = true;
                }

                $analyzed[] = [
                    'category' => $item['category'],
                    'comment' => $item['comment'],
                    'sentiment' => $nada,
                    'confidence' => $keyakinan,
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

            $pesan = count($saved) > 1
                ? count($saved) . ' masukan terkirim. Terima kasih sudah menuliskannya.'
                : 'Masukanmu terkirim. Terima kasih sudah menuliskannya.';

            if ($adaYangBelumDianalisis) {
                $pesan .= ' Nada masukannya belum bisa dianalisis sekarang, tapi isinya sudah tersimpan.';
            }

            // Form di website mengharapkan halaman, bukan JSON. Jawaban JSON tetap disediakan
            // untuk pemanggil lain, misalnya kalau nanti ada aplikasi ponsel.
            if ($request->expectsJson()) {
                return response()->json(['message' => $pesan, 'data' => $saved], 201);
            }

            return redirect('/feedback')->with('sukses', $pesan);

        } catch (Throwable $e) {
            report($e);

            $pesan = 'Masukanmu belum bisa diproses sekarang. Coba lagi beberapa saat lagi.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $pesan, 'error' => $e->getMessage()], 503);
            }

            return back()->withInput()->with('gagal', $pesan);
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
