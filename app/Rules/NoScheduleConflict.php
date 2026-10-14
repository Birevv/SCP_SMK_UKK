<?php

namespace App\Rules;

use App\Models\Schedule;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoScheduleConflict implements ValidationRule
{
    public function __construct(
        protected string $dayOfWeek,
        protected string $startTime,
        protected string $endTime,
        protected string $cycleType,
        protected ?int $classroomId = null,
        protected ?int $roomId = null,
        protected ?int $teacherId = null,
        protected ?int $ignoreId = null,
        protected ?string $type = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($this->dayOfWeek) || empty($this->startTime) || empty($this->endTime) || empty($this->cycleType)) {
            return;
        }

        $query = Schedule::where('day_of_week', $this->dayOfWeek)
            ->where('cycle_type', $this->cycleType)
            ->where(function ($q) {
                $q->where('start_time', '<', $this->endTime)
                    ->where('end_time', '>', $this->startTime);
            });

        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        // 1. Validasi khusus Ruangan
        if ($this->type === 'room' || ($attribute === 'room_id' && $this->type === null)) {
            if ($this->roomId) {
                $conflict = (clone $query)->with('subject')->where('room_id', $this->roomId)->first();
                if ($conflict) {
                    $subjectName = $conflict->subject?->name ?? 'Mata Pelajaran';
                    $fail("Ruangan sudah digunakan oleh mata pelajaran {$subjectName} pada jam tersebut.");
                }
            }

            return;
        }

        // 2. Validasi khusus Kelas
        if ($this->type === 'classroom' || ($attribute === 'classroom_id' && $this->type === null)) {
            if ($this->classroomId) {
                $conflict = (clone $query)->with('subject')->where('classroom_id', $this->classroomId)->first();
                if ($conflict) {
                    $subjectName = $conflict->subject?->name ?? 'Mata Pelajaran';
                    $fail("Kelas sudah digunakan untuk mata pelajaran {$subjectName} pada jam tersebut.");
                }
            }

            return;
        }

        // 3. Validasi khusus Guru
        if ($this->type === 'teacher' || ($attribute === 'teacher_id' && $this->type === null)) {
            if ($this->teacherId) {
                $conflict = (clone $query)->with('subject')->where('teacher_id', $this->teacherId)->first();
                if ($conflict) {
                    $subjectName = $conflict->subject?->name ?? 'Mata Pelajaran';
                    $fail("Guru tersebut sudah mengajar mata pelajaran {$subjectName} di kelas lain pada jam tersebut.");
                }
            }

            return;
        }

        // 4. Fallback jika dipanggil umum (misal di unit test)
        $conflict = $query->with('subject')->where(function ($q) {
            if ($this->roomId) {
                $q->orWhere('room_id', $this->roomId);
            }
            if ($this->classroomId) {
                $q->orWhere('classroom_id', $this->classroomId);
            }
            if ($this->teacherId) {
                $q->orWhere('teacher_id', $this->teacherId);
            }
        })->first();

        if ($conflict) {
            $subjectName = $conflict->subject?->name ?? 'Mata Pelajaran';
            if ($conflict->room_id == $this->roomId) {
                $fail("Ruangan sudah digunakan oleh mata pelajaran {$subjectName} pada jam tersebut.");
            } elseif ($conflict->classroom_id == $this->classroomId) {
                $fail("Kelas sudah digunakan untuk mata pelajaran {$subjectName} pada jam tersebut.");
            } elseif ($conflict->teacher_id == $this->teacherId) {
                $fail("Guru tersebut sudah mengajar mata pelajaran {$subjectName} di kelas lain pada jam tersebut.");
            }
        }
    }
}
