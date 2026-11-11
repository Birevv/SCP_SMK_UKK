<?php

namespace App\Http\Controllers\Api\Homeroom;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Classroom;
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
        return User::whereHas('role', fn ($q) => $q->where('name', 'siswa'))
            ->whereHas('classrooms', fn ($q) => $q->where('classrooms.id', $classroomId))
            ->pluck('id');
    }

    public function classrooms(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $isSuperAdmin = $teacher->hasRole('Super Admin') || $teacher->role?->name === 'Super Admin';
        $isBK = $teacher->hasRole('Guru BK') || $teacher->role?->name === 'Guru BK';

        if ($isSuperAdmin) {
            $classrooms = Classroom::select('id', 'name')->orderBy('name')->get();
        } elseif ($isBK) {
            $classrooms = $teacher->classrooms()->select('classrooms.id', 'classrooms.name')->orderBy('name')->get();
            if ($classrooms->isEmpty() && $teacher->classroom_id && $teacher->classroom) {
                $classrooms = collect([$teacher->classroom]);
            }
        } else {
            // Wali Kelas hanya mengampu 1 kelas
            $singleClassroom = $teacher->classroom ?? $teacher->classrooms()->select('classrooms.id', 'classrooms.name')->first();
            $classrooms = $singleClassroom ? collect([$singleClassroom]) : collect();
        }

        return $this->successResponse($classrooms, 'Daftar Kelas Binaan Berhasil Dimuat');
    }

    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $isBK = $teacher->hasRole('Guru BK') || $teacher->role?->name === 'Guru BK';
        $isSuperAdmin = $teacher->hasRole('Super Admin') || $teacher->role?->name === 'Super Admin';

        if ($isSuperAdmin) {
            $teacherClassrooms = Classroom::all();
        } elseif ($isBK) {
            $teacherClassrooms = $teacher->classrooms;
            if ($teacherClassrooms->isEmpty() && $teacher->classroom_id && $teacher->classroom) {
                $teacherClassrooms = collect([$teacher->classroom]);
            }
        } else {
            // Wali Kelas hanya mengampu 1 kelas
            $singleClassroom = $teacher->classroom ?? $teacher->classrooms->first();
            $teacherClassrooms = $singleClassroom ? collect([$singleClassroom]) : collect();
        }
        $teacherClassroomIds = $teacherClassrooms->pluck('id');

        $classroom = null;
        $selectedClassroomId = null;

        if ($request->filled('classroom_id') && $request->classroom_id !== 'all' && $request->classroom_id !== '0') {
            $classroom = Classroom::find($request->classroom_id);
            if (! $classroom) {
                return $this->errorResponse('Kelas tidak ditemukan', 404);
            }
            if (! $isSuperAdmin && $teacherClassroomIds->isNotEmpty() && ! $teacherClassroomIds->contains($classroom->id)) {
                return $this->errorResponse('Anda tidak memiliki akses ke kelas ini', 403);
            }
            $studentsIds = $this->getStudentIds($classroom->id);
            $classroomName = $classroom->name;
            $selectedClassroomId = $classroom->id;
        } elseif ($request->filled('grade') || $request->filled('class_level')) {
            $classroom = $teacherClassrooms->first();
            if (! $classroom) {
                $classroom = Classroom::forGrade($request->input('grade', $request->input('class_level')))->first();
            }
            $studentsIds = $classroom ? $this->getStudentIds($classroom->id) : collect();
            $classroomName = $classroom?->name ?? 'Kelas Tidak Ditemukan';
            $selectedClassroomId = $classroom?->id;
        } elseif ($isBK) {
            if ($teacherClassroomIds->isNotEmpty()) {
                $studentsIds = User::whereHas('role', fn ($q) => $q->whereIn('name', ['Siswa', 'siswa']))
                    ->whereHas('classrooms', fn ($q) => $q->whereIn('classrooms.id', $teacherClassroomIds))
                    ->pluck('id');
                $classroomName = 'Semua Kelas Binaan';
            } else {
                $studentsIds = collect();
                $classroomName = 'Semua Kelas';
            }
        } elseif ($teacherClassrooms->isNotEmpty()) {
            $classroom = $teacherClassrooms->first();
            $studentsIds = $this->getStudentIds($classroom->id);
            $classroomName = $classroom->name;
            $selectedClassroomId = $classroom->id;
        } else {
            return $this->errorResponse('Anda Belum Ditugaskan Sebagai Wali Kelas / Guru BK', 403);
        }

        $today = Carbon::today();

        $todayAttendances = Attendance::whereIn('student_id', $studentsIds)
            ->whereDate('date', $today)
            ->get();

        $hadir = $todayAttendances->where('status', 'Hadir')->count();
        $izin = $todayAttendances->where('status', 'Izin')->count();
        $sakit = $todayAttendances->where('status', 'Sakit')->count();
        $alpa = $todayAttendances->where('status', 'Alpa')->count();
        $totalStudents = $studentsIds->count();
        $percentage = $totalStudents > 0 ? round(($hadir / $totalStudents) * 100, 1) : 0;

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
            'classroom_name' => $classroomName,
            'classrooms' => $teacherClassrooms->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ])->values(),
            'selected_classroom_id' => $selectedClassroomId,
            'attendance_today' => [
                'hadir' => $hadir,
                'izin' => $izin,
                'sakit' => $sakit,
                'alpa' => $alpa,
                'total_students' => $totalStudents,
                'percentage' => $percentage,
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
