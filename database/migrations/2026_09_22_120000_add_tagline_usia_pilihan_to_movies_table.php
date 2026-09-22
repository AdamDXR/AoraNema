<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            // kalimat pendek di bawah judul, diisi admin. Kosong berarti tidak ditampilkan
            $table->string('tagline')->nullable()->after('title');

            // batas usia penonton dari LSF: SU, 13+, 17+, atau 21+. TMDB tidak menyediakannya
            $table->string('usia', 3)->nullable()->after('duration_minutes');

            // dicentang admin supaya film muncul di bagian Dipilih Pengelola di beranda
            $table->boolean('pilihan')->default(false)->after('is_showing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn(['tagline', 'usia', 'pilihan']);
        });
    }
};
