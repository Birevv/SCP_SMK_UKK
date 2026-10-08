<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreScheduleRequest;
use App\Http\Resources\Api\ScheduleResource;
use App\Models\AcademicYear;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    use ApiResponser;

    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $config = SystemConfiguration::current();

        $query = Schedule::with(['subject', 'classroom', 'room'])
            ->where('teacher_id', $teacher->id);

        if ($request->filled('cycle_type')) {
            $query->where('cycle_type', $request->cycle_type);
        } else {
            $query->forActiveCycle();
        }

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }

        $schedules = $query->orderBy('start_time')->get();

        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        $grouped = [];
        foreach ($days as $day) {
            $grouped[$day] = ScheduleResource::collection(
                $schedules->where('day_of_week', $day)->values()
            );
        }

        return $this->successResponse([
            'schedules' => $grouped,
            'active_cycle' => $config->active_cycle,
            'is_schedule_locked' => $config->is_schedule_locked,
        ], 'Data jadwal berhasil diambil');
    }

    public function store(StoreScheduleRequest $request): JsonResponse
    {
        $teacher = $request->user();
        $config = SystemConfiguration::current();

        if ($config->is_schedule_locked) {
            return $this->errorResponse('Jadwal telah dikunci oleh Waka Kurikulum. Tidak dapat menambah jadwal.', 403);
        }

        $data = $request->validated();

        $conflict = Schedule::where('day_of_week', $data['day_of_week'])
            ->where('cycle_type', $data['cycle_type'])
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time'])
            ->where(function ($q) use ($teacher, $data) {
                $q->where('teacher_id', $teacher->id)
                  ->orWhere('classroom_id', $data['classroom_id'])
                  ->orWhere('room_id', $data['room_id']);
            })
            ->with(['teacher', 'classroom', 'room'])
            ->first();

        if ($conflict) {
            $detail = 'Bentrok dengan: ';
            if ($conflict->teacher_id == $teacher->id) {
                $detail .= 'Jadwal Anda Sendiri';
            } elseif ($conflict->classroom_id == $data['classroom_id']) {
                $detail .= 'Kelas ' . $conflict->classroom?->name;
            } else {
                $detail .= 'Ruang ' . $conflict->room?->name;
            }

            return $this->errorResponse('Jadwal bentrok terdeteksi', 422, [
                'conflict_type' => 'schedule',
                'detail' => $detail,
                'conflicting_schedule' => new ScheduleResource($conflict),
            ]);
        }

        $data['teacher_id'] = $teacher->id;
        $data['academic_year_id'] = Schedule::where('teacher_id', $teacher->id)->latest()->value('academic_year_id')
            ?? AcademicYear::latest()->value('id');

        $schedule = Schedule::create($data);
        $schedule->load(['subject', 'classroom', 'room']);

        return $this->successResponse(new ScheduleResource($schedule), 'Jadwal berhasil ditambahkan', 201);
    }

    public function students(Schedule $schedule, Request $request): JsonResponse
    {
        $teacher = $request->user();

        if ($schedule->teacher_id !== $teacher->id) {
            return $this->errorResponse('Anda tidak memiliki akses ke jadwal ini', 403);
        }

        $classroomId = $schedule->classroom_id;
        $today = Carbon::today();

        $students = User::whereHas('role', fn ($q) => $q->where('name', 'Siswa'))
            ->whereHas('classrooms', fn ($q) => $q->where('classrooms.id', $classroomId))
            ->orderBy('full_name')
            ->get()
            ->map(function (User $student) use ($today) {
                $approvedLeave = LeaveRequest::where('student_id', $student->id)
                    ->where('status', 'Approved')
                    ->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today)
                    ->first();

                return [
                    'id' => $student->id,
                    'full_name' => $student->full_name,
                    'identity_number' => $student->identity_number,
                    'has_approved_leave' => $approvedLeave !== null,
                    'leave_reason' => $approvedLeave?->reason_type,
                ];
            });

        return $this->successResponse([
            'schedule' => [
                'id' => $schedule->id,
                'subject_name' => $schedule->subject?->name,
                'classroom_name' => $schedule->classroom?->name,
                'room_name' => $schedule->room?->name,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
            ],
            'students' => $students,
        ], 'Data siswa berhasil diambil');
    }

    public function mySchedules(Request $request): JsonResponse
    {
        return $this->index($request);
    }

    public function storeSchedule(StoreScheduleRequest $request): JsonResponse
    {
        return $this->store($request);
    }

    public function getStudents(Schedule $schedule, Request $request): JsonResponse
    {
        return $this->students($schedule, $request);
    }
}
