<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb', 'pgsql'])) {
            try {
                DB::statement('ALTER TABLE schedules DROP CONSTRAINT chk_schedule_time');
            } catch (Throwable $e) {
                // Abaikan jika constraint belum pernah dibuat
            }

            DB::statement('ALTER TABLE schedules ADD CONSTRAINT chk_schedule_time CHECK (start_time < end_time)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb', 'pgsql'])) {
            try {
                DB::statement('ALTER TABLE schedules DROP CONSTRAINT chk_schedule_time');
            } catch (Throwable $e) {
                // Abaikan jika constraint sudah dihapus
            }
        }
    }
};
