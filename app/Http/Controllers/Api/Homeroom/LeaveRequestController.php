<?php

namespace App\Http\Controllers\Api\Homeroom;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
