<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceReportPrintController extends Controller
{
    public function print(Request $request)
    {
        $classroomId = $request->query('classroom_id');
        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', Carbon::now()->endOfMonth()->toDateString());

        if (! $classroomId) {
            $classroomId = Classroom::first()?->id;
        }

        $selectedClassroom = Classroom::find($classroomId);
        $students = User::where('classroom_id', $classroomId)->get();

        $report = [];

        foreach ($students as $student) {
            $attendances = Attendance::where('student_id', $student->id)
                ->whereHas('journal', function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('date', [$startDate, $endDate]);
                })
                ->get();

            $hadir = $attendances->where('status', 'Hadir')->count();
            $sakit = $attendances->where('status', 'Sakit')->count();
            $izin = $attendances->where('status', 'Izin')->count();
            $alpa = $attendances->where('status', 'Alpa')->count();
            $totalPertemuan = $attendances->count();

            $percentage = $totalPertemuan > 0 ? round(($hadir / $totalPertemuan) * 100, 1) : 100;

            $report[] = [
                'student_id' => $student->id,
                'full_name' => $student->full_name,
                'username' => $student->username,
                'hadir' => $hadir,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpa' => $alpa,
                'total' => $totalPertemuan,
                'percentage' => $percentage,
            ];
        }

        return view('print.attendance-report-print', [
            'classroom' => $selectedClassroom,
            'start_date' => Carbon::parse($startDate)->isoFormat('D MMMM Y'),
            'end_date' => Carbon::parse($endDate)->isoFormat('D MMMM Y'),
            'students' => $report,
        ]);
    }
}
