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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            // buat tau siapa yang pesan
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // nonton jam berapanya
            $table->foreignId('showtime_id')->constrained()->cascadeOnDelete();
            // kursi mana yang dipesan
            $table->foreignId('seat_id')->constrained()->cascadeOnDelete();

            $table->integer('price');
            $table->string('status')->default('pending'); 
            //nanti ada pending, paid, dan cancelled
            $table->timestamps();

            // biar nggak ada jam dan kursi yang ke double
            $table->unique(['showtime_id', 'seat_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
