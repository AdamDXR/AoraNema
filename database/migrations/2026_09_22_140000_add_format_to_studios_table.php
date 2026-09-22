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
            // format layar yang tampil ke penonton: Regular 2D, Regular 3D, atau IMAX.
            // dipisah dari nama, supaya bioskop bisa punya banyak studio dengan format yang sama
            $table->string('format')->default('Regular 2D')->after('name');
        });

        // studio lama yang namanya berisi format ikut diberi format yang sesuai
        foreach (DB::table('studios')->get() as $studio) {
            $format = match (true) {
                str_contains(strtoupper($studio->name), 'IMAX') => 'IMAX',
                str_contains(strtoupper($studio->name), '3D') => 'Regular 3D',
                default => 'Regular 2D',
            };

            DB::table('studios')->where('id', $studio->id)->update(['format' => $format]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            $table->dropColumn('format');
        });
    }
};
