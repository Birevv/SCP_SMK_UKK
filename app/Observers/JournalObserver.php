<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\User;

class JournalObserver
{
    /**
     * Saat Journal dibuat, otomatis generate attendance records untuk semua siswa di kelas.
     * Siswa dengan leave request yang sudah Approved akan di-set status Izin/Sakit.
     */
    public function created(Journal $journal): void
    {
        $classroomId = $journal->schedule?->classroom_id;
        if (! $classroomId) {
            return;
        }

        // Ambil semua student IDs (khusus role Siswa) melalui pivot
        $studentIds = User::whereHas('role', function ($query) {
            $query->where('name', 'Siswa');
        })->whereHas('classrooms', function ($query) use ($classroomId) {
            $query->where('classrooms.id', $classroomId);
        })->pluck('id');
        if ($studentIds->isEmpty()) {
            return;
        }

        // Pre-load semua approved leave requests dalam satu query (hindari N+1)
        $approvedLeaves = LeaveRequest::whereIn('student_id', $studentIds)
            ->where('status', 'Approved')
            ->where('start_date', '<=', $journal->date)
            ->where('end_date', '>=', $journal->date)
            ->get()
            ->keyBy('student_id');

        foreach ($studentIds as $studentId) {
            $approvedLeave = $approvedLeaves->get($studentId);

            if ($approvedLeave) {
                Attendance::updateOrCreate(
                    [
                        'journal_id' => $journal->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'status' => $approvedLeave->reason_type,
                        'date' => $journal->date,
                        'notes' => 'Otomatis tersinkronisasi dari Surat Izin (Approved oleh Wali Kelas)',
                    ]
                );
            } else {
                Attendance::firstOrCreate(
                    [
                        'journal_id' => $journal->id,
                        'student_id' => $studentId,
                    ],
                    [
                        'status' => 'Hadir',
                        'date' => $journal->date,
                        'notes' => null,
                    ]
                );
            }
        }
    }
}
