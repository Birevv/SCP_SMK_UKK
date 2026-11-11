<?php

namespace App\Http\Controllers\Api\Homeroom;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    use ApiResponser;

    protected function getHomeroomClassroom(User $teacher)
    {
        return $teacher->classrooms()->first();
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
        $classroomName = 'Semua Siswa Binaan';

        if ($request->filled('classroom_id') && $request->classroom_id !== 'all' && $request->classroom_id !== '0') {
            $classroom = Classroom::find($request->classroom_id);
            if (! $classroom) {
                return $this->errorResponse('Kelas tidak ditemukan', 404);
            }
            if (! $isSuperAdmin && $teacherClassroomIds->isNotEmpty() && ! $teacherClassroomIds->contains($classroom->id)) {
                return $this->errorResponse('Anda tidak memiliki akses ke kelas ini', 403);
            }
            $targetClassroomIds = collect([$classroom->id]);
            $classroomName = $classroom->name;
        } elseif ($isBK) {
            // Guru BK bisa mengampu dan memfilter banyak kelas
            $targetClassroomIds = $teacherClassroomIds;
            $classroomName = 'Semua Siswa Binaan';
        } elseif ($teacherClassrooms->isNotEmpty()) {
            // Wali Kelas hanya mengampu 1 kelas
            $classroom = $teacherClassrooms->first();
            $targetClassroomIds = collect([$classroom->id]);
            $classroomName = $classroom->name;
        } else {
            return $this->errorResponse('Anda Belum Ditugaskan Sebagai Wali Kelas / Guru BK', 403);
        }

        $activeYear = AcademicYear::getActive();
        $today = Carbon::today('Asia/Jakarta');

        $studentsQuery = User::whereHas('role', fn ($q) => $q->whereIn('name', ['Siswa', 'siswa']))
            ->with(['classrooms' => fn ($q) => $q->select('classrooms.id', 'classrooms.name')])
            ->orderBy('full_name');

        if ($targetClassroomIds->isNotEmpty()) {
            $studentsQuery->whereHas('classrooms', fn ($q) => $q->whereIn('classrooms.id', $targetClassroomIds));
        }

        $students = $studentsQuery->get();
        $studentIds = $students->pluck('id');

        // Query attendances for the active year
        $attendancesQuery = Attendance::whereIn('student_id', $studentIds);
        if ($activeYear) {
            $attendancesQuery->where(function ($q) use ($activeYear) {
                $q->whereHas('journal.schedule', function ($sq) use ($activeYear) {
                    $sq->where('academic_year_id', $activeYear->id);
                });
                if ($activeYear->start_date && $activeYear->end_date) {
                    $q->orWhereBetween('date', [$activeYear->start_date, $activeYear->end_date]);
                }
            });
        }
        $allAttendances = $attendancesQuery->get()->groupBy('student_id');

        // Query today's approved leave requests
        $todayLeaves = LeaveRequest::whereIn('student_id', $studentIds)
            ->where('status', 'Approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->get()
            ->keyBy('student_id');

        // Query today's attendance records
        $todayAttendances = Attendance::whereIn('student_id', $studentIds)
            ->whereDate('date', $today)
            ->get()
            ->keyBy('student_id');

        $data = $students->map(function (User $student) use ($classroom, $allAttendances, $todayLeaves, $todayAttendances) {
            $attList = $allAttendances->get($student->id, collect());
            $hadir = $attList->where('status', 'Hadir')->count();
            $sakit = $attList->where('status', 'Sakit')->count();
            $izin = $attList->where('status', 'Izin')->count();
            $alpa = $attList->where('status', 'Alpa')->count();
            $total = $attList->count();
            $percentage = $total > 0 ? round(($hadir / $total) * 100, 1) : null;

            $approvedLeave = $todayLeaves->get($student->id);
            $todayAtt = $todayAttendances->get($student->id);

            $todayStatus = $todayAtt?->status ?? ($approvedLeave ? ($approvedLeave->reason_type === 'Sakit' ? 'Sakit' : 'Izin') : 'Hadir');

            $studentClassroom = $student->classrooms->first() ?? $classroom;

            return [
                'id' => $student->id,
                'student_name' => $student->full_name,
                'full_name' => $student->full_name,
                'identity_number' => $student->identity_number,
                'nisn' => $student->identity_number,
                'classroom_id' => $studentClassroom?->id ?? $classroom?->id,
                'classroom_name' => $studentClassroom?->name ?? $classroom?->name ?? '-',
                'hadir' => $hadir,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpa' => $alpa,
                'total' => $total,
                'percentage' => $percentage,
                'today_status' => $todayStatus,
                'is_auto_synced' => $approvedLeave !== null,
                'leave_reason' => $approvedLeave?->reason_type,
            ];
        });

        return $this->successResponse([
            'classroom' => [
                'id' => $classroom?->id,
                'name' => $classroomName,
            ],
            'classrooms' => $teacherClassrooms->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ])->values(),
            'total_students' => $students->count(),
            'students' => $data,
        ], 'Daftar siswa binaan berhasil dimuat');
    }
}
