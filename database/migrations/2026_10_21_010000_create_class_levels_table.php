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
        Schema::create('class_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // X, XI, XII, Lulus
            $table->integer('level')->nullable(); // 10, 11, 12, 0
            $table->string('description')->nullable(); // Kelas 10, Kelas 11, Kelas 12, Alumni / Lulus
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_levels');
    }
};
