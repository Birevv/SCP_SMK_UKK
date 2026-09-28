<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreJournalRequest;
use App\Http\Requests\Api\StoreScheduleRequest;
use App\Http\Resources\Api\JournalResource;
use App\Http\Resources\Api\ScheduleResource;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherApiController extends Controller
{
    use ApiResponser;

    public function dashboard(Request $request): JsonResponse
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
                    'classroom_name' => $schedule->room?->name,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'has_journal' => $hasJournal,
                ];
            });

            $now = Carbon::now();
            $pendingJournals = $todaySchedules->filter(function ($s) use ($now) {
                $endTime = Carbon::parse($s['end_time']);
                return !$s['has_journal'] && $now->greaterThan($endTime->addMinutes(30));
            })->values()->map(fn($s) => [
                'schedule_id' => $s['id'],
                'subject_name' => $s['subject_name'],
                'classroom_name' => $s['classroom_name'],
                'message' => 'Jurnal Belum Diisi (>30 Menit Terlambat)',
            ]);

            return $this->successResponse([
                'teacher_name' => $teacher->full_name,
                'current_time' => $now->format('H:i'),
                'current_date' => $today->locale('id')->isoFormat('dddd, D MMMM Y'),
                'today_schedule' => $todaySchedules,
                'pending_journals' => $pendingJournals,
                'active_cycle' => $config->active_cycle,
                'is_schedule_locked' => $config->is_schedule_locked,
            ], 'Data Dashboard Berhasil Diambil');
    }

    public function mySchedules(Request $request): JsonResponse
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
        ], 'Data Jadwal Berhasil Diambil');
    }

    public function storeSchedule(StoreScheduleRequest $request): JsonResponse
    {
        $teacher = $request->user();
        $config = SystemConfiguration::current();

        if ($config->is_schedule_locked) {
            return $this->errorResponse('Jadwal Telah Dikunci Oleh Waka Kurikulum. Tidak Dapat Menambah Jadwal', 403);
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
            $detail = 'Bentrok Dengan: ';
            if ($conflict->teacher_id == $teacher->id) {
                $detail .= 'Jadwal Anda Sendiri';
            } elseif($conflict->classroom_id == $data['classroom_id']) {
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

    public function getStudents(Schedule $schedule, Request $request): JsonResponse
    {
        $teacher = $request->user();

        if ($schedule->teacher_id !== $teacher->id) {
            return $this->errorResponse('Anda Tidak Memiliki Akses Ke Jadwal Ini', 403);
        }
        $classroomId = $schedule->classroom_id;
        $today = Carbon::today();

        $students = User::whereHas('role', fn($q) => $q->where('name', 'Siswa'))
            ->whereHas('classrooms', fn($q) => $q->where('classrooms.id', $classroomId))
            ->orderBy('full_name')
            ->get()
            ->map(function(User $student) use ($today) {
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
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                ],
                'students' => $students,
            ], 'Data Siswa Berhasil Diambil');
    }

    public function storeJournal (StoreJournalRequest $request): JsonResponse
    {
        $teacher = $request->user();
        $data = $request->validated();

        $schedule = Schedule::findOrFail($data['schedule_id']);
        if ($schedule->teacher_id !== $teacher->id) {
            return $this->errorResponse('Anda Tidak Memiliki Akses Ke Jadwal Ini', 403);
        }

        if ($request->hasFile('activity_photo')) {
            $data['activity_photo'] = $request->file('activity_photo')->store('journal_photos', 'public');
        }
        
        $data['teacher_id'] = $teacher->id;
        $attendancesData = $data['attendances'];
        unset($data['attendances']);

        $journal = Journal::create($data);

        foreach ($attendancesData as $att) {
            Attendance::updateOrCreate(
                [
                    'journal_id' => $journal->id,
                    'student_id' => $att['student_id'],
                ],
                [
                    'status' => $att['status'],
                    'date' => $data['date'],
                    'notes' => $att['notes'] ?? null,
                ]
            );
        }   
        
        $journal->load(['schedule.subject', 'schedule.classroom', 'attendances']);

        return $this->successResponse(new JournalResource($journal), 'Jurnal Dan Presensi Berhasil Disimpan', 201);        
    }

    public function journalHistory(Request $request): JsonResponse
    {
        $teacher = $request->user();

        $query = Journal::with(['schedule.subject', 'schedule.classroom', 'attendances'])
            ->where('teacher_id', $teacher->id)
            ->latest('date');

        // Filter bulan
        if ($request->filled('month')) {
            $query->whereMonth('date', $request->month);
        }

        // Filter tahun
        if ($request->filled('year')) {
            $query->whereYear('date', $request->year);
        }

        // Filter cycle
        if ($request->filled('cycle_type')) {
            $query->whereHas('schedule', fn ($q) => $q->where('cycle_type', $request->cycle_type));
        }

        $perPage = $request->input('per_page', 15);
        $journals = $query->paginate($perPage);

        return $this->successResponse(
            JournalResource::collection($journals)->response()->getData(true),
            'Riwayat jurnal berhasil diambil'
        );
    }
}
