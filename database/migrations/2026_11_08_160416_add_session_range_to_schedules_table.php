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
        Schema::table('schedules', function (Blueprint $table) {
            $table->unsignedInteger('start_session')->nullable()->after('day_of_week');
            $table->unsignedInteger('end_session')->nullable()->after('start_session');

            $table->index(['day_of_week', 'start_session', 'end_session']);
        });

        // Mapping historis dari jadwal impor sebelumnya ke nomor sesi (1 - 10)
        $startMap = [
            'Senin' => [
                '08:00' => 1, '09:10' => 3, '10:00' => 4, '10:35' => 5,
                '11:10' => 6, '12:45' => 7, '13:25' => 8, '14:00' => 9, '14:35' => 10,
            ],
            'Selasa' => [
                '07:30' => 1, '08:50' => 3, '09:45' => 4, '10:25' => 5,
                '11:05' => 6, '12:45' => 7, '13:25' => 8, '14:00' => 9,
            ],
            'Rabu' => [
                '07:15' => 1, '08:35' => 3, '09:30' => 4, '10:10' => 5,
                '10:50' => 6, '12:30' => 7, '13:10' => 8, '13:50' => 9, '14:30' => 10,
            ],
            'Kamis' => [
                '07:15' => 1, '07:55' => 2, '08:35' => 3, '09:30' => 4, '10:10' => 5,
                '10:50' => 6, '12:30' => 7, '13:10' => 8, '13:50' => 9,
            ],
            'Jumat' => [
                '07:15' => 1, '07:55' => 2, '08:35' => 3, '09:30' => 4, '10:10' => 5,
                '10:50' => 6, '12:40' => 7, '13:15' => 8,
            ],
        ];

        $endMap = [
            'Senin' => [
                '09:10' => 2, '09:45' => 3, '10:35' => 4, '11:10' => 5,
                '11:45' => 6, '13:25' => 7, '14:00' => 8, '14:35' => 9, '15:10' => 10,
            ],
            'Selasa' => [
                '08:50' => 2, '09:30' => 3, '10:25' => 4, '11:05' => 5,
                '11:45' => 6, '13:25' => 7, '14:00' => 8, '15:10' => 10,
            ],
            'Rabu' => [
                '08:35' => 2, '09:15' => 3, '10:10' => 4, '10:50' => 5,
                '11:30' => 6, '13:10' => 7, '13:50' => 8, '14:30' => 9, '15:10' => 10,
            ],
            'Kamis' => [
                '07:55' => 1, '08:35' => 2, '09:15' => 3, '10:10' => 4, '10:50' => 5,
                '11:30' => 6, '13:10' => 7, '13:50' => 8, '15:10' => 10,
            ],
            'Jumat' => [
                '07:55' => 1, '08:35' => 2, '09:15' => 3, '10:10' => 4, '10:50' => 5,
                '11:25' => 6, '13:15' => 7, '13:50' => 8,
            ],
        ];

        // 1. Backfill start_session dan end_session ke jadwal yang sudah ada
        $schedules = DB::table('schedules')->select('id', 'day_of_week', 'start_time', 'end_time')->get();
        foreach ($schedules as $s) {
            $st = substr((string) $s->start_time, 0, 5);
            $et = substr((string) $s->end_time, 0, 5);
            $startSess = $startMap[$s->day_of_week][$st] ?? null;
            $endSess = $endMap[$s->day_of_week][$et] ?? null;

            if ($startSess !== null && $endSess !== null) {
                DB::table('schedules')->where('id', $s->id)->update([
                    'start_session' => $startSess,
                    'end_session' => $endSess,
                ]);
            }
        }

        // 2. Sinkronkan start_time dan end_time dengan school_sessions aktif saat ini jika ada
        $activeSessions = DB::table('school_sessions')->get()->groupBy('day_of_week');
        if ($activeSessions->isNotEmpty()) {
            foreach ($activeSessions as $day => $sessions) {
                $sessByNumber = $sessions->keyBy('session_number');
                $daySchedules = DB::table('schedules')
                    ->where('day_of_week', $day)
                    ->whereNotNull('start_session')
                    ->whereNotNull('end_session')
                    ->get();

                foreach ($daySchedules as $ds) {
                    $sStart = $sessByNumber->get($ds->start_session);
                    $sEnd = $sessByNumber->get($ds->end_session);

                    if ($sStart && $sEnd) {
                        $newStartTime = substr((string) $sStart->start_time, 0, 5).':00';
                        $newEndTime = substr((string) $sEnd->end_time, 0, 5).':00';

                        DB::table('schedules')->where('id', $ds->id)->update([
                            'start_time' => $newStartTime,
                            'end_time' => $newEndTime,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex(['day_of_week', 'start_session', 'end_session']);
            $table->dropColumn(['start_session', 'end_session']);
        });
    }
};
