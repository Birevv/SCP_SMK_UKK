<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Storage;

class LeaveRequestObserver
{
    /**
     * Handle the LeaveRequest "updated" event.
     * Saat Surat Izin disetujui (Approved), otomatis sinkronkan data presensi pada jurnal yang ada.
     */
    public function updated(LeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->wasChanged('status') && $leaveRequest->status === 'Approved') {
            $this->syncAttendance($leaveRequest);
        }
    }

    /**
     * Handle the LeaveRequest "created" event if created directly as Approved.
     */
    public function created(LeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->status === 'Approved') {
            $this->syncAttendance($leaveRequest);
        }
    }

    /**
     * Handle the LeaveRequest "deleting" event.
     */
    public function deleting(LeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->attachment_photo && Storage::disk('public')->exists($leaveRequest->attachment_photo)) {
            Storage::disk('public')->delete($leaveRequest->attachment_photo);
        }
    }

    private function syncAttendance(LeaveRequest $leaveRequest): void
    {
        $student = $leaveRequest->student;
        if (! $student) {
            return;
        }

        $classroomIds = $student->classrooms()->pluck('classrooms.id');
        if ($classroomIds->isEmpty()) {
            return;
        }

        // Cari semua jurnal di kelas siswa tersebut pada rentang tanggal izin
        $journals = Journal::whereHas('schedule', function ($q) use ($classroomIds) {
            $q->whereIn('classroom_id', $classroomIds);
        })
            ->whereBetween('date', [$leaveRequest->start_date, $leaveRequest->end_date])
            ->get();

        $reasonStr = $leaveRequest->reason_type instanceof \App\Enums\ReasonTypeEnum
            ? $leaveRequest->reason_type->value
            : (string) $leaveRequest->reason_type;

        $normalizedStatus = match (strtolower(str_replace(' ', '_', $reasonStr))) {
            'sakit' => 'Sakit',
            default => 'Izin',
        };

        foreach ($journals as $journal) {
            Attendance::updateOrCreate(
                [
                    'journal_id' => $journal->id,
                    'student_id' => $student->id,
                ],
                [
                    'status' => $normalizedStatus,
                    'date' => $journal->date,
                    'notes' => 'Otomatis tersinkronisasi dari Pengajuan Izin Approved'.($leaveRequest->leave_time ? ' (Sesi Mulai '.substr((string) $leaveRequest->leave_time, 0, 5).')' : ''),
                ]
            );
        }
    }
}
