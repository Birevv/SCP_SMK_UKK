<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Room;
use App\Models\Subject;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferenceController extends Controller
{
    use ApiResponser;

    public function classrooms(): JsonResponse {
        $classrooms = Classroom::select('id', 'name')->orderBy('name')->get();

        return $this->successResponse($classrooms, 'Data Kelas Berhasil Dimuat');
    }

    public function rooms(): JsonResponse {
        $rooms = Room::select('id', 'name')->orderBy('name')->get();

        return $this->successResponse($rooms, 'Data Ruang Berhasil Dimuat');
    }

    public function subjects(): JsonResponse {
        $subjects = Subject::select('id', 'name')->orderBy('name')->get();

        return $this->successResponse($subjects, 'Data Mata Pelajaran Berhasil Dimuat');
    }
}
