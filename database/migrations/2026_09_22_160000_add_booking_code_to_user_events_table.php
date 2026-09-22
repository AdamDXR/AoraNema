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
        Schema::table('user_events', function (Blueprint $table) {
            // penilaian terikat ke satu pesanan, bukan ke film. penonton yang menonton film yang sama
            // dua kali memberi nilai terpisah untuk tiap pesanannya
            $table->string('booking_code')->nullable()->after('movie_id');
            $table->index(['user_id', 'event_type', 'booking_code']);
        });

        // penilaian lama (satu per film) dipindahkan ke pesanan terakhir penonton itu untuk film tersebut
        foreach (DB::table('user_events')->where('event_type', 'rate')->whereNull('booking_code')->get() as $nilai) {
            $kode = DB::table('bookings')
                ->join('showtimes', 'showtimes.id', '=', 'bookings.showtime_id')
                ->where('bookings.user_id', $nilai->user_id)
                ->where('showtimes.movie_id', $nilai->movie_id)
                ->where('bookings.status', 'paid')
                ->orderByDesc('showtimes.show_time')
                ->value('bookings.booking_code');

            DB::table('user_events')->where('id', $nilai->id)->update(['booking_code' => $kode]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_events', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'event_type', 'booking_code']);
            $table->dropColumn('booking_code');
        });
    }
};
