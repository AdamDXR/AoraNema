<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('beranda');
});

Route::get('/film/{slug}', function (string $slug) {
    $daftar = require resource_path('data/film.php');

    $film = collect($daftar)->firstWhere('slug', $slug);

    abort_if($film === null, 404);

    // Tanggal dibatasi ke enam hari yang memang ditampilkan di halaman, supaya
    // parameter di URL tidak bisa dipakai meminta jadwal sembarang tanggal.
    $hariIni = now()->startOfDay();
    $tanggal = $hariIni;
    $diminta = request('tanggal');

    for ($i = 1; $i < 6; $i++) {
        $kandidat = $hariIni->copy()->addDays($i);

        if ($kandidat->format('Y-m-d') === $diminta) {
            $tanggal = $kandidat;
            break;
        }
    }

    return view('film', compact('film', 'tanggal'));
})->where('slug', '[a-z0-9-]+');

Route::get('/masuk', function () {
    return view('masuk');
});
