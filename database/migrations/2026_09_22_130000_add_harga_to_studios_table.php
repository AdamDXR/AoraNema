<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            // harga per kursi ditentukan studio dan harinya, bukan jam tayangnya.
            // akhir pekan dihitung Jumat sampai Minggu, seperti kebanyakan bioskop di Indonesia
            $table->integer('harga_biasa')->default(45000)->after('capacity');
            $table->integer('harga_akhir_pekan')->default(55000)->after('harga_biasa');
        });

        // jadwal yang belum lewat ikut disesuaikan dengan tarif studionya.
        // jadwal lama dibiarkan, dan harga di pesanan yang sudah dibuat tidak ikut berubah
        foreach (DB::table('showtimes')->where('show_time', '>=', now())->get() as $jadwal) {
            $studio = DB::table('studios')->find($jadwal->studio_id);
            $akhirPekan = in_array(date('w', strtotime($jadwal->show_time)), ['5', '6', '0']);

            DB::table('showtimes')->where('id', $jadwal->id)->update([
                'price' => $akhirPekan ? $studio->harga_akhir_pekan : $studio->harga_biasa,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            $table->dropColumn(['harga_biasa', 'harga_akhir_pekan']);
        });
    }
};
