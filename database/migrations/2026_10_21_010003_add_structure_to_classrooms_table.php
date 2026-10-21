<?php

use App\Models\ClassLevel;
use App\Models\Major;
use App\Models\Rombel;
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
        Schema::table('classrooms', function (Blueprint $table) {
            $table->foreignIdFor(ClassLevel::class)->nullable()->after('name')->constrained()->nullOnDelete();
            $table->foreignIdFor(Major::class)->nullable()->after('class_level_id')->constrained()->nullOnDelete();
            $table->foreignIdFor(Rombel::class)->nullable()->after('major_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Rombel::class);
            $table->dropConstrainedForeignIdFor(Major::class);
            $table->dropConstrainedForeignIdFor(ClassLevel::class);
        });
    }
};
