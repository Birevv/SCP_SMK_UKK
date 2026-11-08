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

        if ($request->filled('classroom_id')) {
            $classroom = Classroom::find($request->classroom_id);
            if (! $classroom) {
                return $this->errorResponse('Kelas tidak ditemukan', 404);
            }
            if (! $isBK && ! $teacher->hasRole('Super Admin')) {
                $teacherClassroomIds = $teacher->classrooms()->pluck('classrooms.id');
                if (! $teacherClassroomIds->contains($classroom->id)) {
                    return $this->errorResponse('Anda tidak memiliki akses ke kelas ini', 403);
                }
            }
        } elseif ($isBK) {
            $classroom = Classroom::orderBy('name')->first();
        } else {
            $classroom = $this->getHomeroomClassroom($teacher);
        }

        if (! $classroom) {
            return $this->errorResponse('Anda Belum Ditugaskan Sebagai Wali Kelas', 403);
        }

        $activeYear = AcademicYear::getActive();
        $today = Carbon::today('Asia/Jakarta');

        $students = User::whereHas('role', fn ($q) => $q->whereIn('name', ['Siswa', 'siswa']))
            ->whereHas('classrooms', fn ($q) => $q->where('classrooms.id', $classroom->id))
            ->orderBy('full_name')
            ->get();

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

            return [
                'id' => $student->id,
                'student_name' => $student->full_name,
                'full_name' => $student->full_name,
                'identity_number' => $student->identity_number,
                'nisn' => $student->identity_number,
                'classroom_id' => $classroom->id,
                'classroom_name' => $classroom->name,
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
                'id' => $classroom->id,
                'name' => $classroom->name,
            ],
            'total_students' => $students->count(),
            'students' => $data,
        ], 'Daftar siswa binaan berhasil dimuat');
    }
}
