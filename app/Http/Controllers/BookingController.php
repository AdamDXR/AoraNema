<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Movie;
use App\Models\Showtime;
use App\Models\Studio;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function pilihKursi(Request $request, $slug)
    {
        // 1. Cari film berdasarkan slug URL (misal: 'coyote-vs-acme-3' -> ambil angka 3)
        $parts = explode('-', $slug);
        $id = end($parts);
        $movie = Movie::findOrFail($id);

        // 2. Tangkap parameter dari URL yang dikirim oleh halaman detail film
        $layar = $request->query('layar'); // Contoh: "Regular 2D" atau "IMAX"
        $jam = $request->query('jam');
        $tanggal = $request->query('tanggal');
        $jumlahTiket = $request->query('jumlah', 1);

        // 3. Tentukan Studio berdasarkan jenis layar yang dipilih
        $namaStudio = str_contains(strtolower($layar), 'imax') ? 'Studio 2 (IMAX)' : 'Studio 1 (Regular)';
        
        // Ambil studio beserta seluruh kursinya (A1 - E10)
        $studio = Studio::with('seats')->where('name', $namaStudio)->firstOrFail();

        // 4. Ambil jadwal tayang (Showtime) untuk film dan studio ini
        $showtime = Showtime::where('movie_id', $movie->id)
                            ->where('studio_id', $studio->id)
                            ->first();

        // 5. Cari ID kursi yang sudah dipesan orang lain pada jadwal tersebut
        $bookedSeatIds = Booking::where('showtime_id', $showtime->id ?? 0)
                                ->pluck('seat_id')
                                ->toArray();

        // Ubah ID kursi menjadi array nomor kursi (misal: ['A1', 'A2', 'C5']) agar mudah dibaca frontend
        $kursiTerisi = $studio->seats->whereIn('id', $bookedSeatIds)->pluck('seat_number')->toArray();

        // 6. Lempar semua data ini ke teman frontend Anda di file kursi.blade.php
        return view('kursi', compact(
            'movie', 
            'studio', 
            'layar', 
            'jam', 
            'tanggal', 
            'jumlahTiket', 
            'kursiTerisi' // Frontend tinggal mengecek: if in_array(kursi, kursiTerisi) -> disable tombolnya
        ));
    }
}