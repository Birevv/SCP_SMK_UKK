<?php

namespace App\Http\Controllers\Api\Homeroom;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponser;

    protected function getHomeroomClassroom(User $teacher)
    {
        return $teacher->classrooms()->first();
    }

    protected function getStudentIds(int $classroomId)
    {
        return User::whereHas('role', fn($q) => $q->where('name', 'siswa'))
            ->whereHas('classrooms', fn($q) => $q->where('classrooms.id', $classroomId))
            ->pluck('id');
    }

    public function index(Request $request):JsonResponse
    {
        $teacher = $request->user();
        $classroom = $this->getHomeroomClassroom($teacher);

        if (!$classroom) {
            return $this->errorResponse('Anda Belum Ditugaskan Sebagai Wali Kelas', 403);
        }

        $studentsIds = $this->getStudentIds($classroom->id);
        $today = Carbon::today();

        $todayAttendances = Attendance::whereIn('student_id', $studentsIds)
            ->whereDate('date', $today)
            ->get();

        $hadir = $todayAttendances->where('status', 'Hadir')->count();
        $izin = $todayAttendances->where('status', 'Izin')->count();
        $sakit = $todayAttendances->where('status', 'Sakit')->count();
        $alpa = $todayAttendances->where('status', 'Alpa')->count();
        $totalStudents = $studentsIds->count();
        $percentage = $totalStudents > 0 ? round(($hadir / $totalStudents) * 100, 1): 0;

        $pendingLeaves = LeaveRequest::with('student')
            ->whereIn('student_id', $studentsIds)
            ->where('status', 'Pending')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (LeaveRequest $lr) => [
                'id' => $lr->id,
                'student_name' => $lr->student?->full_name,
                'reason_type' => $lr->reason_type,
                'description' => $lr->description,
                'start_date' => $lr->start_date?->format('Y-m-d'),
                'end_date' => $lr->end_date?->format('Y-m-d'),
                'status' => $lr->status,
            ]);

        $pendingCount = LeaveRequest::whereIn('student_id', $studentsIds)
            ->where('status', 'Pending')
            ->count();

        return $this->successResponse([
            'teacher_name' => $teacher->full_name,
            'classroom_name' => $classroom->name,
            'attendance_today' => [
                'hadir' => $hadir,
                'izin' => $izin,
                'sakit' => $sakit,
                'alpa' => $alpa,
                'total_students' => $totalStudents,
                'precentage' => $percentage, 
            ],
            'pending_leave_requests' => $pendingLeaves,
            'pending_count' => $pendingCount, 
        ], 'Dashboard Wali Kelas Berhasil Diambil');
    }

    public function dashboard(Request $request): JsonResponse
    {
        return $this->index($request);
    }
}
