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
        Schema::table('users', function (Blueprint $table) {
            $table->string('graduation_year', 10)->nullable()->after('status')->comment('Tahun Kelulusan / Angkatan Alumni');
            $table->date('graduated_at')->nullable()->after('graduation_year')->comment('Tanggal Resmi Kelulusan');
            $table->text('alumni_notes')->nullable()->after('graduated_at')->comment('Catatan Alumni / Tujuan Setelah Lulus (Kuliah, Kerja, Wirausaha, dsb)');
        });

        Schema::table('user_class', function (Blueprint $table) {
            $table->string('status', 30)->default('Aktif')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['graduation_year', 'graduated_at', 'alumni_notes']);
        });

        Schema::table('user_class', function (Blueprint $table) {
            $table->enum('status', ['Aktif', 'Tinggal Kelas', 'Mutasi'])->default('Aktif')->change();
        });
    }
};
