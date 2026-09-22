<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Movie extends Model
{
    // biar kolom 'id' gak bisa diisi sembarangan misalnya lewat form input
    protected $guarded = ['id'];

    // relasi ke tabel genre (many-to-many)
    public function genres(): BelongsToMany
    {
        // ada tambahan ->withTimestamps() biar otomatis ikut nyatet tanggal dibuat dan diupdate-nya di tabel pivot 'genre_movie'
        return $this->belongsToMany(Genre::class)->withTimestamps();
    }

    // relasi ke tabel showtimes (One-to-many)
    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtime::class);
    }

    // relasi ke tabel log activity machine learning (One-to-many)
    public function userEvents(): HasMany
    {
        return $this->hasMany(UserEvent::class);
    }

    // Isi kartu film yang sama untuk beranda dan halaman /film, supaya keterangan di bawah
    // poster tidak pernah berbeda di dua tempat itu. Relasi genres harus sudah dimuat.
    public function kartu(): array
    {
        $namaBulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $genre = $this->genres->pluck('name');
        $rilis = $this->release_date ? Carbon::parse($this->release_date) : null;

        return [
            'slug' => Str::slug($this->title) . '-' . $this->id,
            'judul' => $this->title,
            'poster' => $this->alamatPoster(),
            'tagline' => $this->tagline,
            'genre' => $genre->implode(', '),
            'durasi' => $this->duration_minutes,
            'durasiTeks' => $this->duration_minutes
                ? intdiv($this->duration_minutes, 60) . 'j ' . ($this->duration_minutes % 60) . 'm'
                : null,
            // Diisi admin. TMDB tidak menyediakan batas usia Indonesia, jadi film dari seeder
            // kosong dan tandanya tidak ditampilkan sampai admin mengisinya.
            'usia' => $this->usia,
            'pilihan' => (bool) $this->pilihan,
            'format' => $this->formatTayang(),
            'rilis' => $this->release_date,
            'tayang' => (bool) $this->is_showing,
            'mulaiTeks' => ! $this->is_showing && $rilis?->isFuture()
                ? $rilis->day . ' ' . $namaBulan[$rilis->month]
                : null,
        ];
    }

    // Seeder TMDB menyimpan alamat lengkap, sedangkan admin boleh mengisi nama berkas di
    // public/img/. Keduanya diubah jadi alamat yang bisa langsung dipakai di <img>.
    public function alamatPoster(): ?string
    {
        if (! $this->poster_url) {
            return null;
        }

        return Str::startsWith($this->poster_url, ['http://', 'https://'])
            ? $this->poster_url
            : asset('img/' . $this->poster_url);
    }

    // Jadwal yang belum lewat, dipakai untuk tahu format apa saja yang sedang ditawarkan.
    public function jadwalMendatang(): HasMany
    {
        return $this->hasMany(Showtime::class)->where('show_time', '>=', now());
    }

    // Format layar yang benar-benar ada di jadwal mendatang film ini, disingkat untuk kartu:
    // 2D, 3D, IMAX. Film tanpa jadwal mendatang tidak menampilkan format sama sekali.
    // Muat jadwalMendatang.studio di query supaya tidak ada query tambahan per film.
    public function formatTayang(): array
    {
        $jadwal = $this->relationLoaded('jadwalMendatang')
            ? $this->jadwalMendatang
            : $this->jadwalMendatang()->with('studio')->get();

        $ada = $jadwal->pluck('studio.format')->unique()->all();
        $singkat = ['Regular 2D' => '2D', 'Regular 3D' => '3D', 'IMAX' => 'IMAX'];

        return collect(Studio::FORMAT)
            ->filter(fn ($f) => in_array($f, $ada))
            ->map(fn ($f) => $singkat[$f])
            ->values()
            ->all();
    }
}
