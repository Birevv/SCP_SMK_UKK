<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponser;

    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $today = Carbon::today();
        $dayName = $today->locale('id')->isoFormat('dddd');
        $config = SystemConfiguration::current();

        $todaySchedules = Schedule::with(['subject', 'classroom', 'room'])
            ->where('teacher_id', $teacher->id)
            ->where('day_of_week', $dayName)
            ->forActiveCycle()
            ->orderBy('start_time')
            ->get()
            ->map(function (Schedule $schedule) use ($today) {
                $hasJournal = Journal::where('schedule_id', $schedule->id)
                    ->where('date', $today)
                    ->exists();

                return [
                    'id' => $schedule->id,
                    'subject_name' => $schedule->subject?->name,
                    'classroom_name' => $schedule->classroom?->name,
                    'room_name' => $schedule->room?->name,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'has_journal' => $hasJournal,
                ];
            });

        $now = Carbon::now();
        $pendingJournals = $todaySchedules->filter(function ($s) use ($now) {
            $endTime = Carbon::parse($s['end_time']);

            return ! $s['has_journal'] && $now->greaterThan($endTime->addMinutes(30));
        })->values()->map(fn ($s) => [
            'schedule_id' => $s['id'],
            'subject_name' => $s['subject_name'],
            'classroom_name' => $s['classroom_name'],
            'message' => 'Jurnal belum diisi (>30 menit terlambat)',
        ]);

        return $this->successResponse([
            'teacher_name' => $teacher->full_name,
            'current_time' => $now->format('H:i'),
            'current_date' => $today->locale('id')->isoFormat('dddd, D MMMM Y'),
            'today_schedules' => $todaySchedules,
            'pending_journals' => $pendingJournals,
            'active_cycle' => $config->active_cycle,
            'is_schedule_locked' => $config->is_schedule_locked,
        ], 'Data dashboard guru berhasil diambil');
    }

    public function dashboard(Request $request): JsonResponse
    {
        return $this->index($request);
    }
}
