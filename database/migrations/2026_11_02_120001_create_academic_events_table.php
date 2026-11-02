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
        Schema::create('academic_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->cascadeOnDelete();
            $table->string('title');
            $table->string('category', 50)->default('kegiatan_siswa');
            $table->json('target_grades')->nullable()->comment('["X", "XI", "XII"] atau NULL jika berlaku seluruh sekolah');
            $table->boolean('is_holiday')->default(false)->comment('Tanggal merah / libur resmi');
            $table->boolean('disable_kbm')->default(false)->comment('KBM / jadwal guru ditiadakan untuk target_grades');
            $table->boolean('requires_attendance')->default(true)->comment('Apakah siswa tetap wajib presensi (misal Upacara 17 Agustus)');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_all_day')->default(true);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['start_date', 'end_date'], 'idx_events_dates');
            $table->index('category', 'idx_events_category');
            $table->index(['is_holiday', 'disable_kbm', 'requires_attendance'], 'idx_events_flags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_events');
    }
};

