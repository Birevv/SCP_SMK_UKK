<?php

namespace App\Http\Controllers\Api;

use App\Enums\AcademicEventCategory;
use App\Http\Controllers\Controller;
use App\Models\AcademicEvent;
use App\Models\Classroom;
use App\Models\Room;
use App\Models\Subject;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferenceController extends Controller
{
    use ApiResponser;

    public function classrooms(): JsonResponse
    {
        $classrooms = Classroom::select('id', 'name')->orderBy('name')->get();

        return $this->successResponse($classrooms, 'Data Kelas Berhasil Dimuat');
    }

    public function rooms(): JsonResponse
    {
        $rooms = Room::select('id', 'name')->orderBy('name')->get();

        return $this->successResponse($rooms, 'Data Ruang Berhasil Dimuat');
    }

    public function subjects(): JsonResponse
    {
        $subjects = Subject::select('id', 'name')->orderBy('name')->get();

        return $this->successResponse($subjects, 'Data Mata Pelajaran Berhasil Dimuat');
    }

    public function calendarEvents(Request $request): JsonResponse
    {
        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);
        $grade = $request->query('grade');

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $query = AcademicEvent::query()
            ->forDateRange($start, $end);

        if ($grade) {
            $query->where(function ($q) use ($grade) {
                $q->whereNull('target_grades')
                    ->orWhereJsonContains('target_grades', $grade);
            });
        }

        $events = $query->orderBy('start_date')->get()->map(function (AcademicEvent $e) {
            return [
                'id' => $e->id,
                'title' => $e->title,
                'category' => $e->category instanceof AcademicEventCategory ? $e->category->value : (string) $e->category,
                'category_label' => $e->category instanceof AcademicEventCategory ? $e->category->getLabel() : 'Kegiatan',
                'color' => $e->color,
                'target_grades' => $e->target_grades,
                'is_holiday' => (bool) $e->is_holiday,
                'disable_kbm' => (bool) $e->disable_kbm,
                'requires_attendance' => (bool) $e->requires_attendance,
                'start_date' => $e->start_date?->toDateString(),
                'end_date' => $e->end_date?->toDateString(),
                'is_all_day' => (bool) $e->is_all_day,
                'start_time' => $e->start_time,
                'end_time' => $e->end_time,
                'description' => $e->description,
            ];
        });

        return $this->successResponse($events, 'Data Kalender Akademik Berhasil Dimuat');
    }
}
