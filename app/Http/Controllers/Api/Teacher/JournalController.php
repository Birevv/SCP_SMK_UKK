<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreJournalRequest;
use App\Http\Resources\Api\JournalResource;
use App\Models\Attendance;
use App\Models\Journal;
use App\Models\Schedule;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class JournalController extends Controller
{
    use ApiResponser;

    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();

        if ($teacher && ($teacher->hasRole('Guru BK') || $teacher->role?->name === 'Guru BK') && ! $teacher->hasRole('Super Admin')) {
            return $this->errorResponse('Guru BK tidak diizinkan melihat jurnal mengajar', 403);
        }

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

    public function store(StoreJournalRequest $request): JsonResponse
    {
        $teacher = $request->user();

        if ($teacher && ($teacher->hasRole('Guru BK') || $teacher->role?->name === 'Guru BK') && ! $teacher->hasRole('Super Admin')) {
            return $this->errorResponse('Guru BK tidak diizinkan menginput jurnal mengajar', 403);
        }

        try {
            $data = $request->validated();
        } catch (\Throwable $e) {
            $data = $request->all();
        }

        $schedule = Schedule::findOrFail($data['schedule_id']);
        if ($schedule->teacher_id !== $teacher->id) {
            return $this->errorResponse('Anda tidak memiliki akses ke jadwal ini', 403);
        }

        $alreadyFilled = Journal::where('schedule_id', $data['schedule_id'])
            ->whereDate('date', $data['date'])
            ->exists();

        if ($alreadyFilled) {
            return $this->errorResponse('Jurnal untuk jadwal mengajar ini pada tanggal yang dipilih sudah pernah diisi.', 422);
        }

        $uploadedPhotoPath = null;
        if ($request->hasFile('activity_photo')) {
            $uploadedPhotoPath = $request->file('activity_photo')->store('journal_photos', 'public');
            $data['activity_photo'] = $uploadedPhotoPath;
        }

        $data['teacher_id'] = $teacher->id;
        $attendancesData = $data['attendances'];
        unset($data['attendances']);

        try {
            $journal = DB::transaction(function () use ($data, $attendancesData) {
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

                return $journal;
            });
        } catch (\Throwable $e) {
            if ($uploadedPhotoPath && Storage::disk('public')->exists($uploadedPhotoPath)) {
                Storage::disk('public')->delete($uploadedPhotoPath);
            }

            report($e);

            return $this->errorResponse('Gagal menyimpan jurnal dan presensi: '.$e->getMessage(), 500);
        }

        $journal->load(['schedule.subject', 'schedule.classroom', 'attendances']);

        return $this->successResponse(new JournalResource($journal), 'Jurnal dan presensi berhasil disimpan', 201);
    }

    public function journalHistory(Request $request): JsonResponse
    {
        return $this->index($request);
    }

    public function storeJournal(StoreJournalRequest $request): JsonResponse
    {
        return $this->store($request);
    }
}
