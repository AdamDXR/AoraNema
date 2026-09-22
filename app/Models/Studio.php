<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Studio extends Model
{
    protected $guarded = ['id'];

    // Satu studio memiliki banyak kursi
    public function seats(): HasMany
    {
        return $this->hasMany(Seat::class);
    }

    // Satu studio dipakai untuk banyak jadwal tayang
    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtime::class);
    }

    // Harga per kursi di studio ini pada tanggal tertentu. Akhir pekan dihitung Jumat sampai
    // Minggu, seperti kebanyakan bioskop di Indonesia.
    public function hargaUntuk(\Carbon\CarbonInterface $tanggal): int
    {
        return in_array($tanggal->dayOfWeek, [5, 6, 0]) ? $this->harga_akhir_pekan : $this->harga_biasa;
    }

    // Jadwal yang belum lewat disesuaikan lagi dengan tarif studio, dipanggil setelah tarifnya diubah.
    // Harga di pesanan yang sudah dibuat tidak ikut berubah karena disimpan sendiri di tabel bookings.
    public function sesuaikanHargaJadwal(): void
    {
        $this->showtimes()->where('show_time', '>=', now())->get()
            ->each(fn ($jadwal) => $jadwal->update(['price' => $this->hargaUntuk($jadwal->show_time)]));
    }
}
