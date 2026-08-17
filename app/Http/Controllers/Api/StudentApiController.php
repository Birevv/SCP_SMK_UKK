<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLeaveRequest;
use App\Http\Resources\Api\LeaveRequestResource;
use App\Models\Attendance;
use App\Models\LeaveRequest;
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

        $attendances = Attendance::whereBelongsTo($student, 'student')->get();

        $hadir = $attendances->where('status', 'Hadir')->count();
        $sakit = $attendances->where('status', 'Sakit')->count();
        $izin  = $attendances->where('status', 'Izin')->count();
        $alpa  = $attendances->where('status', 'Alpa')->count();
        $total = $attendances->count();

        $percentage = $total > 0 ? round(($hadir / $total) * 100, 1) : 0;

        $recentHistory = Attendance::with(['journal.schedule.teacher'])
            ->whereBelongsTo($student, 'student')
            ->latest('date')
            ->take(10)
            ->get()
            ->map(fn (Attendance $item) => [
                'id'           => $item->id,
                'date'         => $item->date ? Carbon::parse($item->date)->format('Y-m-d') : null,
                'subject_name' => $item->journal?->schedule?->subject_name ?? '-',
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
}

