<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLeaveRequest;
use App\Http\Resources\Api\AttendanceHistoryResource;
use App\Http\Resources\Api\LeaveRequestResource;
use App\Http\Resources\Api\ScheduleResource;
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

    public function attendanceSummary(Request $request): JsonResponse
    {
        $student = $request->user();
        $today = Carbon::today();
        $attendances = Attendance::whereBelongsTo($student, 'student')
            ->whereMonth('date', $today->month)
            ->whereYear('date', $today->year)
            ->get();
        
        $dayName = $today->locale('id')->isoFormat('dddd');

        $classroomIds = $student->classrooms()->pluck('classrooms_id');
        $classroomName = $student->classrooms->pluck('name')->join(', ');

        $hadir = $attendances->where('status', 'Hadir')->count();
        $sakit = $attendances->where('status', 'Sakit')->count();
        $izin  = $attendances->where('status', 'Izin')->count();
        $alpa  = $attendances->where('status', 'Alpa')->count();
        $total = $attendances->count();

        $percentage = $total > 0 ? round(($hadir / $total) * 100, 1) : 0;

        $recentHistory = Attendance::with(['journal.schedule.teacher', 'journal.schedule.subject'])
            ->whereBelongsTo($student, 'student')
            ->latest('date')
            ->take(10)
            ->get()
            ->map(fn (Attendance $item) => [
                'id'           => $item->id,
                'date'         => $item->date ? Carbon::parse($item->date)->format('Y-m-d') : null,
                'subject_name' => $item->journal?->schedule?->subject?->name ?? '-',
                'teacher_name' => $item->journal?->teacher?->full_name ?? '-',
                'status'       => $item->status,
                'notes'        => $item->notes,
            ]);

        return $this->successResponse([
            'student_name' => $student->full_name,
            'classroom'    => $student->classroom?->name,
            'statistics'   => [
                'hadir'      => $hadir,
                'sakit'      => $sakit,
                'izin'       => $izin,
                'alpa'       => $alpa,
                'total'      => $total,
                'percentage' => $percentage,
            ],
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

    public function dashboard(Request $request): JsonResponse
    {
        $student = $request->user();
        $today = Carbon::today();
        $dayName = $today->locale('id')->isoFormat('dddd');

        $classroomIds = $student->classrooms()->pluck('classrooms.id');
        $classroomName = $student->classrooms->pluck('name')->join(', ');

        $attendances = Attendance::whereBelongsTo($student, 'student')
            ->whereMonth('date', $today->month)
            ->whereYear('date', $today->year)
            ->get();

        $hadir = $attendances->where('status', 'Hadir')->count();
        $sakit = $attendances->where('status', 'Sakit')->count();
        $izin  = $attendances->where('status', 'Izin')->count();
        $alpa  = $attendances->where('status', 'Alpa')->count();
        $total = $attendances->count();
        $percentage = $total > 0 ? round(($hadir / $total) * 100, 1) : 0;

        $todaySchedules = Schedule::with(['subject', 'classroom', 'room'])
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
            'attendance_summary' => [
                'month' => $today->locale('id')->isoFormat('MMMM'),
                'hadir' => $hadir,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpa' => $alpa,
                'total' => $total,
                'percentage' => $percentage,
            ],
            'today_schedules' => ScheduleResource::collection($todaySchedules),
            'pending_leave_count' => $pendingLeaveCount,
        ], 'Dashboard siswa berhasil diambil');
    }

    public function todaySchedule(Request $request): JsonResponse
    {
        $student = $request->user();
        $dayName = Carbon::today()->locale('id')->isoFormat('dddd');

        $classroomIds = $student->classrooms()->pluck('classrooms.id');

        $schedules = Schedule::with(['subject', 'classroom', 'room'])
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

