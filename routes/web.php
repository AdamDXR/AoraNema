<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

// Beberapa pemeriksaan dipakai lebih dari satu halaman, jadi ditaruh sekali di sini.

$ambilFilm = function (string $slug) {
    $film = collect(require resource_path('data/film.php'))->firstWhere('slug', $slug);

    abort_if($film === null, 404);

    return $film;
};

// Tanggal dibatasi ke enam hari yang memang ditampilkan di halaman, supaya parameter
// di URL tidak bisa dipakai meminta jadwal sembarang tanggal.
$ambilTanggal = function () {
    $hariIni = now()->startOfDay();

    for ($i = 1; $i < 6; $i++) {
        $kandidat = $hariIni->copy()->addDays($i);

        if ($kandidat->format('Y-m-d') === request('tanggal')) {
            return $kandidat;
        }
    }

    return $hariIni;
};

$ambilLayarDanJam = function () {
    $tarif = require resource_path('data/tarif.php');

    $layar = request('layar');
    abort_unless(array_key_exists($layar, $tarif), 404);

    $jam = (string) request('jam');
    abort_unless(preg_match('/^\d{2}:\d{2}$/', $jam), 404);

    return [$layar, $jam];
};

// Halaman bayar dan tiket memeriksa hal yang sama persis, cuma beda tampilan.
$pesanan = function (string $slug, string $tampilan) use ($ambilFilm, $ambilTanggal, $ambilLayarDanJam) {
    $film = $ambilFilm($slug);

    abort_if($film['mulai'] !== null, 404);

    [$layar, $jam] = $ambilLayarDanJam();

    $kursi = array_values(array_filter(explode(',', (string) request('kursi'))));

    abort_if(count($kursi) < 1 || count($kursi) > 6, 404);

    foreach ($kursi as $k) {
        abort_unless(preg_match('/^[A-H](10|[1-9])$/', $k), 404);
    }

    $tanggal = $ambilTanggal();
    $akhirPekan = in_array($tanggal->dayOfWeek, [5, 6, 0]);

    $daftarMetode = ['qris' => 'QRIS', 'va' => 'Transfer Bank', 'ewallet' => 'Dompet Digital'];
    $namaMetode = $daftarMetode[request('metode')] ?? 'Belum dipilih';

    return view($tampilan, compact('film', 'tanggal', 'layar', 'jam', 'kursi', 'akhirPekan', 'namaMetode'));
};

Route::get('/', function () {
    return view('beranda');
});

Route::get('/film/{slug}', function (string $slug) use ($ambilFilm, $ambilTanggal) {
    $film = $ambilFilm($slug);
    $tanggal = $ambilTanggal();

    return view('film', compact('film', 'tanggal'));
})->where('slug', '[a-z0-9-]+');

Route::get('/kursi/{slug}', function (string $slug) use ($ambilFilm, $ambilTanggal, $ambilLayarDanJam) {
    $film = $ambilFilm($slug);

    abort_if($film['mulai'] !== null, 404);

    [$layar, $jam] = $ambilLayarDanJam();

    $jumlah = max(1, min(6, (int) request('jumlah', 1)));

    $tanggal = $ambilTanggal();
    $akhirPekan = in_array($tanggal->dayOfWeek, [5, 6, 0]);

    return view('kursi', compact('film', 'tanggal', 'layar', 'jam', 'jumlah', 'akhirPekan'));
})->where('slug', '[a-z0-9-]+');

Route::get('/bayar/{slug}', fn (string $slug) => $pesanan($slug, 'bayar'))
    ->where('slug', '[a-z0-9-]+');

Route::get('/tiket/{slug}', fn (string $slug) => $pesanan($slug, 'tiket'))
    ->where('slug', '[a-z0-9-]+');

Route::get('/masuk', function () {
    return view('masuk');
});

// Saat user membuka halaman awal '/', jalankan fungsi index di HomeController
Route::get('/', [HomeController::class, 'index']);
Route::get('/film/{slug}', [MovieController::class, 'show']);

// Rute untuk tamu (belum login)
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login']);
});

// Rute untuk yang sudah login
Route::post('/keluar', [AuthController::class, 'logout'])->middleware('auth');

// Rute ini hanya bisa diakses oleh pengunjung yang sudah login
Route::middleware('auth')->group(function () {
    // Rute logout yang sudah ada sebelumnya
    Route::post('/keluar', [AuthController::class, 'logout']);
    
    // Rute baru untuk mesin pemesanan kursi
    Route::get('/kursi/{slug}', [BookingController::class, 'pilihKursi']);
});