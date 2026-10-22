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
        Schema::create('school_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('day_of_week'); // Senin, Selasa, Rabu, Kamis, Jumat, Sabtu
            $table->unsignedInteger('session_number'); // 1, 2, ..., 10
            $table->string('label')->nullable(); // 'Jam ke - 1'
            $table->time('start_time'); // '07:15'
            $table->time('end_time'); // '07:55'
            $table->unsignedInteger('duration_minutes')->default(40); // 40
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['day_of_week', 'session_number']);
            $table->index(['day_of_week', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_sessions');
    }
};
