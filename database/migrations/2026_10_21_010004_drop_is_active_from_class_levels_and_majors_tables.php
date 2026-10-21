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
        if (Schema::hasColumn('class_levels', 'is_active')) {
            Schema::table('class_levels', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasColumn('majors', 'is_active')) {
            Schema::table('majors', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_levels', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });

        Schema::table('majors', function (Blueprint $table) {
            $table->boolean('is_active')->default(true);
        });
    }
};
