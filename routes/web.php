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

Route::get('/kursi/{slug}', function (string $slug) {
    $daftar = require resource_path('data/film.php');

    $film = collect($daftar)->firstWhere('slug', $slug);

    abort_if($film === null, 404);
    abort_if($film['mulai'] !== null, 404);

    $tarif = require resource_path('data/tarif.php');

    $layar = request('layar');
    abort_unless(array_key_exists($layar, $tarif), 404);

    $jam = (string) request('jam');
    abort_unless(preg_match('/^\d{2}:\d{2}$/', $jam), 404);

    $jumlah = max(1, min(6, (int) request('jumlah', 1)));

    $hariIni = now()->startOfDay();
    $tanggal = $hariIni;

    for ($i = 1; $i < 6; $i++) {
        $kandidat = $hariIni->copy()->addDays($i);

        if ($kandidat->format('Y-m-d') === request('tanggal')) {
            $tanggal = $kandidat;
            break;
        }
    }

    $akhirPekan = in_array($tanggal->dayOfWeek, [5, 6, 0]);

    return view('kursi', compact('film', 'tanggal', 'layar', 'jam', 'jumlah', 'akhirPekan'));
})->where('slug', '[a-z0-9-]+');



Route::get('/masuk', function () {
    return view('masuk');
});
