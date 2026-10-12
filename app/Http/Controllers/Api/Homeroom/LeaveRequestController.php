<?php

namespace App\Http\Controllers\Api\Homeroom;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\VerifyLeaveRequest;
use App\Http\Resources\Api\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LeaveRequestController extends Controller
{
    use ApiResponser;

    protected function getHomeroomClassroom(User $teacher)
    {
        return $teacher->classrooms()->first();
    }

    protected function getStudentIds(int $classroomId)
    {
        return User::whereHas('role', fn($q) => $q->where('name', 'Siswa'))
            ->whereHas('classrooms', fn($q) => $q->where('classrooms.id', $classroomId))
            ->pluck('id');
    }

    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $classroom = $this->getHomeroomClassroom($teacher);
        
        if (!$classroom) {
            return $this->errorResponse('Anda Belum Ditugaskan Sebagai Wali Kelas', 403);
        }

        $studentIds = $this->getStudentIds($classroom->id);

        $query = LeaveRequest::with(['student', 'verifiedBy'])
            ->whereIn('student_id', $studentIds)
            ->latest();

        if ($request->filled('status') && $request->status !== 'All') {
            $query->where('status', $request->status);
        }

        $perPage = $request->input('per_page', 15);
        $leaveRequests = $query->paginate($perPage);

        return $this->successResponse([
            'leave_requests' => LeaveRequestResource::collection($leaveRequests)->response()->getData(true)
        ], 'Data Permintaan Izin Berhasil Diambil');
    }

    public function show(LeaveRequest $leaveRequest, Request $request): JsonResponse {
        $teacher = $request->user();
        $classroom = $this->getHomeroomClassroom($teacher);

        if (!$classroom) {
            return $this->errorResponse('Anda Belum Ditugaskan Sebagai Wali Kelas', 403);
        }

        $studentIds = $this->getStudentIds($classroom->id);
        if (!$studentIds->contains($leaveRequest->student_id)) {
            return $this->errorResponse('Pengajuan Izin Ini Bukan Milik Siswa Di Kelas Anda', 403);
        }

        $leaveRequest->load('student');
        
        return $this->successResponse([
            'id' => $leaveRequest->id,
            'student' => [
                'full_name' => $leaveRequest->student?->full_name,
                'identity_number' => $leaveRequest->student?->identity_number,
                'classroom' => $classroom->name,
            ],
            'leave_info' => [
                'reason_type' => $leaveRequest->reason_type,
                'start_date' => $leaveRequest->start_date?->format('Y-m-d'),
                'end_date' => $leaveRequest->end_date?->format('Y-m-d'),
                'duartion_days' => $leaveRequest->start_date && $leaveRequest->end_date? $leaveRequest->start_date->diffInDays($leaveRequest->end_date) + 1 : null,
                'description' => $leaveRequest->description,
                'attachment_url' => $leaveRequest->attachment_photo ? Storage::url($leaveRequest->attachment_photo) : null,
                'status' => $leaveRequest->status,
            ],
            'submitted_at' => $leaveRequest->created_at?->format('Y-m-d H:i:s'), 
        ], 'Detail Pengajuan Izin Berhasil Diambil');
    }

    public function verify(VerifyLeaveRequest $request, LeaveRequest $leaveRequest): JsonResponse 
    {
        $teacher = $request->user();
        $classroom = $this->getHomeroomClassroom($teacher);

        if (!$classroom) {
            return $this->errorResponse('Anda Belum Ditugaskan Sebagai Wali Kelas', 403);
        }

        $studentIds = $this->getStudentIds($classroom->id);
        if (!$studentIds->contains($leaveRequest->student_id)) {
            return $this->errorResponse('Pengajuan Izin Ini Bukan Milik Siswa Di Kelas Anda', 403);
        }

        if ($leaveRequest->status !== 'Pending') {
            return $this->errorResponse('Pengajuan Izin Ini Sudah Diverifikasi Sebelumnya', 403);
        }

        $data = $request->validated();
        $leaveRequest->update([
            'status' => $data['status'],
            'verified_by' => $teacher->id,
        ]);

        $statusText = $data['status'] === 'Approved' ? 'Disetujui' : 'Ditolak';

        return $this->successResponse(
            new LeaveRequestResource($leaveRequest->fresh()->load('verifiedBy')),
            "Pengajuan Izin Berhasil {$statusText}"
        );
    }

    public function leaveRequests(Request $request): JsonResponse 
    {
        return $this->index($request);
    }
}
