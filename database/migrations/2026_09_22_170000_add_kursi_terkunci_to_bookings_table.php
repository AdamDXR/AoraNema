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
        Schema::table('bookings', function (Blueprint $table) {
            // berisi seat_id selama pesanan masih berlaku (menunggu bayar atau lunas), dan dikosongkan
            // saat pesanan dibatalkan atau kedaluwarsa. aturan unik dipasang di kolom ini, bukan di
            // seat_id, supaya kursi dari pesanan yang batal bisa dipesan orang lain lagi.
            // MySQL membolehkan banyak NULL di kolom unik, jadi pesanan batal tidak saling bentrok
            $table->unsignedBigInteger('kursi_terkunci')->nullable()->after('seat_id');
        });

        DB::table('bookings')->where('status', '!=', 'cancelled')->update(['kursi_terkunci' => DB::raw('seat_id')]);

        Schema::table('bookings', function (Blueprint $table) {
            // indeks baru dipasang dulu, karena foreign key showtime_id butuh indeks yang diawali kolom itu
            $table->unique(['showtime_id', 'kursi_terkunci']);
            $table->dropUnique(['showtime_id', 'seat_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            // alamat halaman pembayaran Midtrans, supaya penonton bisa melanjutkan pembayaran yang tertunda
            $table->string('snap_url')->nullable()->after('transaction_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('snap_url');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->unique(['showtime_id', 'seat_id']);
            $table->dropUnique(['showtime_id', 'kursi_terkunci']);
            $table->dropColumn('kursi_terkunci');
        });
    }
};
