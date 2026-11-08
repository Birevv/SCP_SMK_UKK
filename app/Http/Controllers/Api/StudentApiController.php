<?php

namespace App\Http\Controllers\Api;

use App\Enums\AcademicEventCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLeaveRequest;
use App\Http\Resources\Api\AttendanceHistoryResource;
use App\Http\Resources\Api\LeaveRequestResource;
use App\Http\Resources\Api\ScheduleResource;
use App\Models\AcademicEvent;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentApiController extends Controller
{
    use ApiResponser;

    protected function getActiveAcademicYear(): ?AcademicYear
    {
        return AcademicYear::getActive();
    }

    protected function getStudentAttendances(Request $request)
    {
        $student = $request->user();
        $activeYear = $this->getActiveAcademicYear();

        $query = Attendance::whereBelongsTo($student, 'student');
        if ($activeYear) {
            $query->where(function ($q) use ($activeYear) {
                $q->whereHas('journal.schedule', function ($sq) use ($activeYear) {
                    $sq->where('academic_year_id', $activeYear->id);
                });
                if ($activeYear->start_date && $activeYear->end_date) {
                    $q->orWhereBetween('date', [$activeYear->start_date, $activeYear->end_date]);
                }
            });
        }

        return $query->get();
    }

    protected function formatAttendanceStatistics($attendances): array
    {
        $hadir = $attendances->where('status', 'Hadir')->count();
        $sakit = $attendances->where('status', 'Sakit')->count();
        $izin = $attendances->where('status', 'Izin')->count();
        $alpa = $attendances->where('status', 'Alpa')->count();
        $total = $attendances->count();

        // Sesuai F-REPO-02: Hadir / Total Pertemuan * 100%
        // Jika total pertemuan == 0, percentage null (atau 0.0) untuk menghindari division by zero
        $percentage = $total > 0 ? round(($hadir / $total) * 100, 1) : null;

        return [
            'hadir' => $hadir,
            'sakit' => $sakit,
            'izin' => $izin,
            'alpa' => $alpa,
            'total' => $total,
            'percentage' => $percentage,
            'has_data' => $total > 0,
        ];
    }

    protected function getTodayAcademicEventData($student, Carbon $today, ?AcademicYear $activeYear): ?array
    {
        $gradeName = $student->classroom?->classLevel?->name;
        $todayEvent = AcademicEvent::query()
            ->forDateRange($today, $today)
            ->where(function ($q) use ($gradeName) {
                $q->whereNull('target_grades')
                    ->orWhereJsonContains('target_grades', $gradeName);
            })
            ->first();

        if (! $todayEvent) {
            return null;
        }

        $category = $todayEvent->category instanceof AcademicEventCategory
            ? $todayEvent->category
            : AcademicEventCategory::tryFrom((string) $todayEvent->category);

        $statusLabel = 'Kegiatan Sekolah';
        if ($todayEvent->is_holiday) {
            $statusLabel = 'Hari Libur';
        } elseif ($todayEvent->disable_kbm) {
            $statusLabel = 'Non-KBM';
        }

        return [
            'id' => $todayEvent->id,
            'title' => $todayEvent->title,
            'category' => $category?->value ?? (string) $todayEvent->category,
            'category_label' => $category?->getLabel() ?? 'Kegiatan Akademik',
            'is_holiday' => (bool) $todayEvent->is_holiday,
            'disable_kbm' => (bool) $todayEvent->disable_kbm,
            'requires_attendance' => (bool) $todayEvent->requires_attendance,
            'is_configured' => true,
            'status_label' => $statusLabel,
        ];
    }

    protected function isCalendarConfigured(?AcademicYear $activeYear): bool
    {
        if ($activeYear) {
            return AcademicEvent::where('academic_year_id', $activeYear->id)->exists();
        }

        return AcademicEvent::exists();
    }

    public function attendanceSummary(Request $request): JsonResponse
    {
        $student = $request->user();
        $today = Carbon::today('Asia/Jakarta');
        $activeYear = $this->getActiveAcademicYear();

        $attendances = $this->getStudentAttendances($request);
        $stats = $this->formatAttendanceStatistics($attendances);
        $todayEventData = $this->getTodayAcademicEventData($student, $today, $activeYear);
        $hasCalendarConfigured = $this->isCalendarConfigured($activeYear);

        $recentHistory = Attendance::with(['journal.schedule.teacher', 'journal.schedule.subject'])
            ->whereBelongsTo($student, 'student')
            ->latest('date')
            ->take(10)
            ->get()
            ->map(fn (Attendance $item) => [
                'id' => $item->id,
                'date' => $item->date ? Carbon::parse($item->date)->format('Y-m-d') : null,
                'subject_name' => $item->journal?->schedule?->subject?->name ?? '-',
                'teacher_name' => $item->journal?->teacher?->full_name ?? '-',
                'status' => $item->status,
                'notes' => $item->notes,
            ]);

        return $this->successResponse([
            'student_name' => $student->full_name,
            'classroom' => $student->classroom?->name,
            'active_academic_year' => $activeYear?->name,
            'today_event' => $todayEventData,
            'has_calendar_configured' => $hasCalendarConfigured,
            'statistics' => $stats,
            'recent_history' => $recentHistory,
        ], 'Ringkasan Presensi Berhasil Diambil');
    }

    public function leaveRequests(Request $request): JsonResponse
    {
        $student = $request->user();

        $requests = LeaveRequest::with('verifiedBy')
            ->whereBelongsTo($student, 'student')
            ->latest()
            ->get();

        return $this->successResponse(LeaveRequestResource::collection($requests), 'Daftar Perizinan Berhasil Diambil');
    }

    public function storeLeaveRequest(StoreLeaveRequest $request): JsonResponse
    {
        $student = $request->user();
        $data = $request->validated();

        if ($request->hasFile('attachment_photo')) {
            $data['attachment_photo'] = $request->file('attachment_photo')->store('leave_attachments', 'public');
        }

        $data['student_id'] = $student->id;
        $data['submitted_by'] = $student->id;
        $data['status'] = 'Pending';

        $leaveRequest = LeaveRequest::create($data);

        return $this->successResponse(new LeaveRequestResource($leaveRequest), 'Surat perizinan berhasil diajukan', 201);
    }

    public function destroyLeaveRequest(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $student = $request->user();

        if ($leaveRequest->student_id !== $student->id && $leaveRequest->submitted_by !== $student->id) {
            return $this->errorResponse('Anda hanya dapat menghapus perizinan yang dibuat sendiri', 403);
        }

        $leaveRequest->delete();

        return $this->successResponse(null, 'Surat perizinan berhasil dihapus');
    }

    public function dashboard(Request $request): JsonResponse
    {
        $student = $request->user();
        $today = Carbon::today('Asia/Jakarta');
        $dayName = $today->locale('id')->isoFormat('dddd');
        $activeYear = $this->getActiveAcademicYear();

        $classroomIds = $student->classrooms()->pluck('classrooms.id');
        $classroomName = $student->classrooms->pluck('name')->join(', ');

        $attendances = $this->getStudentAttendances($request);
        $stats = $this->formatAttendanceStatistics($attendances);
        $todayEventData = $this->getTodayAcademicEventData($student, $today, $activeYear);
        $hasCalendarConfigured = $this->isCalendarConfigured($activeYear);

        $todaySchedules = Schedule::with(['subject', 'classroom', 'room', 'teacher'])
            ->whereIn('classroom_id', $classroomIds)
            ->where('day_of_week', $dayName)
            ->forActiveCycle()
            ->orderBy('start_time')
            ->get();

        $pendingLeaveCount = LeaveRequest::whereBelongsTo($student, 'student')
            ->where('status', 'Pending')
            ->count();

        return $this->successResponse([
            'student_name' => $student->full_name,
            'classroom' => $classroomName,
            'current_date' => $today->locale('id')->isoFormat('dddd, D MMMM Y'),
            'active_academic_year' => $activeYear?->name,
            'today_event' => $todayEventData,
            'has_calendar_configured' => $hasCalendarConfigured,
            'attendance_summary' => array_merge([
                'month' => $today->locale('id')->isoFormat('MMMM'),
            ], $stats),
            'today_schedules' => ScheduleResource::collection($todaySchedules),
            'pending_leave_count' => $pendingLeaveCount,
        ], 'Dashboard siswa berhasil diambil');
    }

    public function schedules(Request $request): JsonResponse
    {
        $student = $request->user();
        $classroomIds = $student->classrooms()->pluck('classrooms.id')->toArray();
        if (empty($classroomIds) && $student->classroom_id) {
            $classroomIds = [$student->classroom_id];
        }

        $query = Schedule::with(['subject', 'classroom', 'room', 'teacher'])
            ->whereIn('classroom_id', $classroomIds);

        if ($request->filled('cycle_type')) {
            $query->where('cycle_type', $request->cycle_type);
        } else {
            $query->forActiveCycle();
        }

        if ($request->filled('day_of_week')) {
            $query->where('day_of_week', $request->day_of_week);
        }

        $dayOrder = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $schedules = $query->orderBy('start_time')->get();

        $schedules = $schedules->sort(function ($a, $b) use ($dayOrder) {
            $dayA = array_search($a->day_of_week, $dayOrder);
            $dayB = array_search($b->day_of_week, $dayOrder);
            $dayA = $dayA === false ? 99 : $dayA;
            $dayB = $dayB === false ? 99 : $dayB;

            if ($dayA === $dayB) {
                return strcmp($a->start_time ?? '', $b->start_time ?? '');
            }

            return $dayA <=> $dayB;
        })->values();

        return $this->successResponse(
            ScheduleResource::collection($schedules),
            'Daftar jadwal pelajaran berhasil diambil'
        );
    }

    public function todaySchedule(Request $request): JsonResponse
    {
        $student = $request->user();
        $dayName = Carbon::today()->locale('id')->isoFormat('dddd');

        $classroomIds = $student->classrooms()->pluck('classrooms.id')->toArray();
        if (empty($classroomIds) && $student->classroom_id) {
            $classroomIds = [$student->classroom_id];
        }

        $schedules = Schedule::with(['subject', 'classroom', 'room', 'teacher'])
            ->whereIn('classroom_id', $classroomIds)
            ->where('day_of_week', $dayName)
            ->forActiveCycle()
            ->orderBy('start_time')
            ->get();

        return $this->successResponse(
            ScheduleResource::collection($schedules),
            'Jadwal hari ini berhasil diambil'
        );
    }

    public function attendanceHistory(Request $request): JsonResponse
    {
        $student = $request->user();

        $query = Attendance::with(['journal.schedule.subject', 'journal.schedule.classroom', 'journal.teacher'])
            ->whereBelongsTo($student, 'student')
            ->latest('date');

        if ($request->filled('month')) {
            $query->whereMonth('date', $request->month);
        }

        if ($request->filled('year')) {
            $query->whereYear('date', $request->year);
        }

        $perPage = $request->input('per_page', 20);
        $attendances = $query->paginate($perPage);

        return $this->successResponse(
            AttendanceHistoryResource::collection($attendances)->response()->getData(true),
            'Riwayat presensi berhasil diambil'
        );
    }
}
