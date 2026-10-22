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
        if (Schema::hasColumn('school_sessions', 'is_active')) {
            Schema::table('school_sessions', function (Blueprint $table) {
                // Drop index if exists before dropping column
                try {
                    $table->dropIndex(['day_of_week', 'is_active']);
                } catch (\Throwable $e) {
                    // ignore if index name differs
                }

                $table->dropColumn('is_active');
                $table->index('day_of_week');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_sessions', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
            $table->index(['day_of_week', 'is_active']);
        });
    }
};
