<?php

namespace Database\Seeders;

use App\Models\Movie;
use App\Models\Showtime;
use App\Models\Studio;
use Illuminate\Database\Seeder;

// Jadwal uji untuk enam hari ke depan, supaya setiap film yang sedang tayang punya jam tayang.
// Jalankan sendiri dengan: php artisan db:seed --class=JadwalContohSeeder
// Tidak dipanggil DatabaseSeeder, karena jadwal asli seharusnya dibuat admin.
class JadwalContohSeeder extends Seeder
{
    public function run(): void
    {
        $film = Movie::where('is_showing', true)->orderBy('id')->get();
        $studio = Studio::orderBy('id')->get();

        if ($film->isEmpty() || $studio->isEmpty()) {
            $this->command->error('Belum ada film yang sedang tayang atau belum ada studio.');

            return;
        }

        $dibuat = 0;
        $giliran = 0;

        for ($hari = 0; $hari < 6; $hari++) {
            $tanggal = now()->startOfDay()->addDays($hari);

            foreach ($studio as $s) {
                // Tiap studio memutar film bergantian dari jam 10:00. Film berikutnya mulai setelah
                // film sebelumnya selesai ditambah jeda, dibulatkan ke atas ke kelipatan 10 menit.
                $mulai = $tanggal->copy()->setTime(10, 0);

                while ($mulai->hour < 22) {
                    $f = $film[$giliran % $film->count()];
                    $giliran++;

                    $bisa = $mulai->isFuture()
                        && ! Showtime::bentrokDengan($s->id, $f->id, $mulai);

                    if ($bisa) {
                        Showtime::create([
                            'movie_id' => $f->id,
                            'studio_id' => $s->id,
                            'show_time' => $mulai->copy(),
                            'price' => $s->hargaUntuk($mulai),
                        ]);
                        $dibuat++;
                    }

                    $menit = ($f->duration_minutes ?: 120) + Showtime::JEDA_MENIT;
                    $mulai->addMinutes((int) ceil($menit / 10) * 10);
                }
            }
        }

        $this->command->info("SELESAI: {$dibuat} jadwal dibuat untuk {$film->count()} film di {$studio->count()} studio.");
    }
}
