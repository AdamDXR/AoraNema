<?php

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

Route::get('/film', function () {
    $semua = require resource_path('data/film.php');

    $cari = trim((string) request('cari'));
    $genre = (string) request('genre');
    $status = in_array(request('status'), ['tayang', 'segera']) ? request('status') : 'semua';

    $daftarGenre = collect($semua)->pluck('genre')->unique()->sort()->values();

    // Genre yang tidak ada di daftar diabaikan, jadi alamat ngawur tidak menghasilkan
    // halaman kosong yang membingungkan.
    if (! $daftarGenre->contains($genre)) {
        $genre = '';
    }

    $film = collect($semua)
        ->when($status === 'tayang', fn ($c) => $c->filter(fn ($f) => $f['mulai'] === null))
        ->when($status === 'segera', fn ($c) => $c->filter(fn ($f) => $f['mulai'] !== null))
        ->when($genre !== '', fn ($c) => $c->filter(fn ($f) => $f['genre'] === $genre))
        ->when($cari !== '', fn ($c) => $c->filter(
            fn ($f) => str_contains(mb_strtolower($f['judul']), mb_strtolower($cari))
        ))
        ->values()
        ->all();

    return view('daftar-film', compact('film', 'daftarGenre', 'cari', 'genre', 'status'));
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

// ---------------------------------------------------------------------------
// Halaman admin. BELUM TERKUNCI karena sistem akun belum ada.
// Begitu login jadi, seluruh grup ini wajib diberi middleware auth dan cek role.
// Ditulis sebagai closure, bukan file controller, supaya tidak menabrak
// controller yang sedang dikerjakan di branch adam/controller.
// ---------------------------------------------------------------------------

Route::prefix('admin')->group(function () {

    Route::get('/', function () {
        return redirect('/admin/film');
    });

    // ----- Film ------------------------------------------------------------

    Route::get('/film', function () {
        $saringan = in_array(request('status'), ['tayang', 'arsip']) ? request('status') : 'semua';

        $film = \App\Models\Movie::with('genres')
            // Jadwal yang belum lewat. Ini yang menunjukkan film mana masih memakan slot studio.
            ->withCount(['showtimes as jadwal_mendatang' => fn ($q) => $q->where('show_time', '>=', now())])
            // Tiket terjual dihitung dari pesanan yang sudah dibayar, lewat jadwalnya.
            ->addSelect(['tiket_terjual' => \App\Models\Booking::query()
                ->selectRaw('count(*)')
                ->join('showtimes', 'showtimes.id', '=', 'bookings.showtime_id')
                ->whereColumn('showtimes.movie_id', 'movies.id')
                ->where('bookings.status', 'paid'),
            ])
            ->when($saringan === 'tayang', fn ($q) => $q->where('is_showing', true))
            ->when($saringan === 'arsip', fn ($q) => $q->where('is_showing', false))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.film.index', [
            'film' => $film,
            'saringan' => $saringan,
            'jumlahTayang' => \App\Models\Movie::where('is_showing', true)->count(),
            'jumlahArsip' => \App\Models\Movie::where('is_showing', false)->count(),
        ]);
    });

    // Mengarsipkan film, bukan menghapusnya. Riwayat penjualan dan jadwal lamanya tetap utuh,
    // film cuma berhenti ditawarkan ke pengunjung.
    Route::post('/film/{movie}/arsip', function (\App\Models\Movie $movie) {
        $movie->update(['is_showing' => ! $movie->is_showing]);

        if ($movie->is_showing) {
            return redirect()->back()->with('sukses', 'Film "' . $movie->title . '" ditayangkan lagi.');
        }

        $mendatang = $movie->showtimes()->where('show_time', '>=', now())->count();

        return redirect()->back()->with(
            'sukses',
            'Film "' . $movie->title . '" diarsipkan.'
                . ($mendatang
                    ? ' Masih ada ' . $mendatang . ' jadwal mendatang yang perlu kamu hapus sendiri di halaman Jadwal Tayang.'
                    : '')
        );
    });

    Route::get('/film/baru', function () {
        $movie = new \App\Models\Movie();
        $genre = \App\Models\Genre::orderBy('name')->get();

        return view('admin.film.form', compact('movie', 'genre'));
    });

    Route::get('/film/{movie}/ubah', function (\App\Models\Movie $movie) {
        $genre = \App\Models\Genre::orderBy('name')->get();

        return view('admin.film.form', compact('movie', 'genre'));
    });

    Route::post('/film', function (\Illuminate\Http\Request $request) {
        $movie = \App\Models\Movie::create(aturanFilm($request));
        $movie->genres()->sync($request->input('genre', []));

        return redirect('/admin/film')->with('sukses', 'Film "' . $movie->title . '" ditambahkan.');
    });

    Route::put('/film/{movie}', function (\Illuminate\Http\Request $request, \App\Models\Movie $movie) {
        $movie->update(aturanFilm($request));
        $movie->genres()->sync($request->input('genre', []));

        return redirect('/admin/film')->with('sukses', 'Film "' . $movie->title . '" disimpan.');
    });

    Route::delete('/film/{movie}', function (\App\Models\Movie $movie) {
        // Menghapus film ikut menghapus jadwal, pesanan, dan pembayarannya, karena
        // semua foreign key memakai cascadeOnDelete. Film yang sudah punya jadwal
        // sebaiknya dimatikan statusnya, bukan dihapus.
        if ($movie->showtimes()->exists()) {
            return redirect('/admin/film')->with(
                'gagal',
                'Film "' . $movie->title . '" punya jadwal tayang, jadi tidak dihapus. '
                    . 'Hilangkan centang "Sedang tayang" kalau mau menariknya dari peredaran.'
            );
        }

        $judul = $movie->title;
        $movie->delete();

        return redirect('/admin/film')->with('sukses', 'Film "' . $judul . '" dihapus.');
    });

    // ----- Studio ----------------------------------------------------------

    Route::get('/studio', function () {
        $studio = \App\Models\Studio::withCount(['seats', 'showtimes'])->orderBy('name')->get();

        return view('admin.studio.index', compact('studio'));
    });

    Route::get('/studio/baru', function () {
        return view('admin.studio.form', [
            'studio' => new \App\Models\Studio(),
            'baris' => 8,
            'perBaris' => 10,
            'terkunci' => false,
        ]);
    });

    Route::get('/studio/{studio}/ubah', function (\App\Models\Studio $studio) {
        // Baris dan kursi per baris tidak disimpan sebagai kolom, jadi dibaca balik
        // dari kursi yang ada: berapa huruf berbeda, dan angka terbesarnya.
        $nomor = $studio->seats()->pluck('seat_number');

        return view('admin.studio.form', [
            'studio' => $studio,
            'baris' => $nomor->map(fn ($k) => substr($k, 0, 1))->unique()->count() ?: 8,
            'perBaris' => $nomor->map(fn ($k) => (int) substr($k, 1))->max() ?: 10,
            'terkunci' => studioTerkunci($studio),
        ]);
    });

    Route::post('/studio', function (\Illuminate\Http\Request $request) {
        $data = aturanStudio($request);

        $studio = \App\Models\Studio::create([
            'name' => $data['name'],
            'capacity' => $data['baris'] * $data['per_baris'],
        ]);

        susunKursi($studio, $data['baris'], $data['per_baris']);

        return redirect('/admin/studio')->with(
            'sukses',
            'Studio "' . $studio->name . '" dibuat dengan ' . $studio->capacity . ' kursi.'
        );
    });

    Route::put('/studio/{studio}', function (\Illuminate\Http\Request $request, \App\Models\Studio $studio) {
        $terkunci = studioTerkunci($studio);
        $data = aturanStudio($request, $terkunci);

        $studio->update(['name' => $data['name']]);

        if (! $terkunci) {
            $studio->update(['capacity' => $data['baris'] * $data['per_baris']]);
            susunKursi($studio, $data['baris'], $data['per_baris']);
        }

        return redirect('/admin/studio')->with(
            'sukses',
            'Studio "' . $studio->name . '" disimpan.'
                . ($terkunci ? ' Susunan kursinya dibiarkan karena sudah ada pesanan.' : '')
        );
    });

    Route::delete('/studio/{studio}', function (\App\Models\Studio $studio) {
        if ($studio->showtimes()->exists()) {
            return redirect('/admin/studio')->with(
                'gagal',
                'Studio "' . $studio->name . '" masih dipakai jadwal tayang, jadi tidak dihapus. '
                    . 'Hapus jadwalnya dulu.'
            );
        }

        $nama = $studio->name;
        $studio->delete();

        return redirect('/admin/studio')->with('sukses', 'Studio "' . $nama . '" dihapus.');
    });

    // ----- Jadwal tayang ---------------------------------------------------

    Route::get('/jadwal', function () {
        $adaFilm = \App\Models\Movie::exists();
        $adaStudio = \App\Models\Studio::exists();

        return view('admin.jadwal.index', [
            'jadwal' => \App\Models\Showtime::with(['movie', 'studio'])
                ->withCount('bookings')
                ->orderByDesc('show_time')
                ->paginate(20),
            'adaFilm' => $adaFilm,
            'adaStudio' => $adaStudio,
            'bisaTambah' => $adaFilm && $adaStudio,
        ]);
    });

    Route::get('/jadwal/baru', function () {
        return view('admin.jadwal.form', [
            'jadwal' => new \App\Models\Showtime(),
            'film' => \App\Models\Movie::orderBy('title')->get(),
            'studio' => \App\Models\Studio::orderBy('name')->get(),
        ]);
    });

    Route::get('/jadwal/{showtime}/ubah', function (\App\Models\Showtime $showtime) {
        return view('admin.jadwal.form', [
            'jadwal' => $showtime,
            'film' => \App\Models\Movie::orderBy('title')->get(),
            'studio' => \App\Models\Studio::orderBy('name')->get(),
        ]);
    });

    Route::post('/jadwal', function (\Illuminate\Http\Request $request) {
        $data = aturanJadwal($request);

        if ($bentrok = jadwalBentrok($data['studio_id'], $data['show_time'])) {
            return back()->withInput()->with('gagal', $bentrok);
        }

        \App\Models\Showtime::create($data);

        return redirect('/admin/jadwal')->with('sukses', 'Jadwal ditambahkan.');
    });

    Route::put('/jadwal/{showtime}', function (\Illuminate\Http\Request $request, \App\Models\Showtime $showtime) {
        $data = aturanJadwal($request);

        if ($bentrok = jadwalBentrok($data['studio_id'], $data['show_time'], $showtime->id)) {
            return back()->withInput()->with('gagal', $bentrok);
        }

        $showtime->update($data);

        return redirect('/admin/jadwal')->with('sukses', 'Jadwal disimpan.');
    });

    Route::delete('/jadwal/{showtime}', function (\App\Models\Showtime $showtime) {
        $showtime->delete();

        return redirect('/admin/jadwal')->with('sukses', 'Jadwal dihapus.');
    });

    // ----- Pesanan ---------------------------------------------------------

    Route::get('/pesanan', function () {
        return view('admin.pesanan.index', [
            'pesanan' => \App\Models\Booking::with(['user', 'seat', 'showtime.movie', 'showtime.studio'])
                ->orderByDesc('id')
                ->paginate(25),
        ]);
    });
});

// ---------------------------------------------------------------------------
// Penolong untuk halaman admin
// ---------------------------------------------------------------------------

// Aturan isian formulir film, dipakai saat menambah maupun mengubah.
function aturanFilm(\Illuminate\Http\Request $request): array
{
    $data = $request->validate([
        'title' => ['required', 'string', 'max:255'],
        'synopsis' => ['nullable', 'string', 'max:5000'],
        'poster_url' => ['nullable', 'string', 'max:255'],
        'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
        'release_date' => ['nullable', 'date'],
        'genre' => ['nullable', 'array'],
        'genre.*' => ['integer', 'exists:genres,id'],
    ], [], [
        'title' => 'judul',
        'synopsis' => 'sinopsis',
        'poster_url' => 'alamat poster',
        'duration_minutes' => 'durasi',
        'release_date' => 'tanggal rilis',
    ]);

    unset($data['genre']);

    // Kotak centang tidak terkirim sama sekali kalau tidak dicentang,
    // jadi nilainya diambil terpisah, bukan lewat validate.
    $data['is_showing'] = $request->boolean('is_showing');

    return $data;
}

// Saat studio terkunci, ukuran ruangnya tidak ikut diperiksa karena isiannya
// dimatikan di halaman sehingga tidak terkirim.
function aturanStudio(\Illuminate\Http\Request $request, bool $terkunci = false): array
{
    $aturan = ['name' => ['required', 'string', 'max:255']];

    if (! $terkunci) {
        $aturan['baris'] = ['required', 'integer', 'min:1', 'max:26'];
        $aturan['per_baris'] = ['required', 'integer', 'min:1', 'max:30'];
    }

    return $request->validate($aturan, [], [
        'name' => 'nama studio',
        'baris' => 'jumlah baris',
        'per_baris' => 'kursi per baris',
    ]);
}

function aturanJadwal(\Illuminate\Http\Request $request): array
{
    return $request->validate([
        'movie_id' => ['required', 'integer', 'exists:movies,id'],
        'studio_id' => ['required', 'integer', 'exists:studios,id'],
        'show_time' => ['required', 'date'],
        'price' => ['required', 'integer', 'min:0', 'max:1000000'],
    ], [], [
        'movie_id' => 'film',
        'studio_id' => 'studio',
        'show_time' => 'waktu tayang',
        'price' => 'harga',
    ]);
}

// Menyusun ulang kursi sebuah studio, dipanggil saat studio dibuat atau diubah.
function susunKursi(\App\Models\Studio $studio, int $baris, int $perBaris): void
{
    $studio->seats()->delete();

    $kursi = [];
    $sekarang = now();

    for ($b = 0; $b < $baris; $b++) {
        for ($n = 1; $n <= $perBaris; $n++) {
            $kursi[] = [
                'studio_id' => $studio->id,
                'seat_number' => chr(65 + $b) . $n,
                'created_at' => $sekarang,
                'updated_at' => $sekarang,
            ];
        }
    }

    \App\Models\Seat::insert($kursi);
}

// Studio yang kursinya sudah dipesan tidak boleh diubah susunannya, karena
// menghapus kursi ikut menghapus pesanan yang menempel padanya.
function studioTerkunci(\App\Models\Studio $studio): bool
{
    return $studio->exists
        && \App\Models\Booking::whereIn('seat_id', $studio->seats()->select('id'))->exists();
}

// Satu studio tidak boleh punya dua jadwal yang mulai pada jam yang sama.
function jadwalBentrok(int $studioId, string $waktu, ?int $kecuali = null): ?string
{
    $bentrok = \App\Models\Showtime::where('studio_id', $studioId)
        ->where('show_time', \Illuminate\Support\Carbon::parse($waktu))
        ->when($kecuali, fn ($q) => $q->whereKeyNot($kecuali))
        ->with('movie')
        ->first();

    if (! $bentrok) {
        return null;
    }

    return 'Studio itu sudah dipakai "' . ($bentrok->movie?->title ?? 'film lain')
        . '" pada jam yang sama. Pilih jam atau studio lain.';
}
