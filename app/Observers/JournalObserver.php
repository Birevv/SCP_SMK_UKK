<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\User;

class JournalObserver
{
    public function created(Journal $journal): void
    {
        $classroomId = $journal->schedule?->classroom_id;
        if (!$classroomId) return;

        $students = User::where('classroom_id', $classroomId)->get();

        foreach ($students as $student) {
            $approvedLeave = LeaveRequest::where('student_id', $student->id)
                ->where('status', 'Approved')
                ->whereDate('start_date', '<=', $journal->date)
                ->whereDate('end_date', '>=', $journal->date)
                ->first();

            if ($approvedLeave) {
                Attendance::updateOrCreate(
                    [
                        'journal_id' => $journal->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'status' => $approvedLeave->reason_type,
                        'notes' => 'Otomatis tersinkronisasi dari Pengajuan izin',
                    ]
                );
            }
        }
    }
}
