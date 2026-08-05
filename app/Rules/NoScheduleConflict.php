<?php

namespace App\Rules;

use App\Models\Schedule;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

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
        protected ?int $ignoreId = null
    ) {}
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = Schedule::where('day_of_week', $this->dayOfWeek)
            ->where('cycle_type', $this->cycleType)
            ->where(function ($q) {
                $q->where('start_time', '<' , $this->endTime)
                  ->where('end_time', '>', $this->startTime); 
            });

        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }
        
        $conflict = $query->where(function ($q) {
            $q->where('room_id', $this->roomId)
              ->orWhere('classroom_id', $this->classroomId)
              ->orWhere('teacher_id', $this->teacherId);
         })->first();

         if($conflict) {
            if ($conflict->room_id == $this->roomId) {
                $fail("Ruangan sudah digunakan oleh mata pelajaran
                {$conflict->subject_name} pada jam tersebut.");           
            } elseif($conflict->classroom_id == $this->classroomId) {
                $fail("Kelas sudah digunakan untuk mata pelajaran
                {$conflict->subject_name} pada jam tersebut.");
            } elseif($conflict->teacher_id == $this->teacherId) {
                $fail("Guru tersebut sudah mengajar mata pelajaran 
                {$conflict->subject_name} di kelas lain pada jam tersebut.");
            }
         }
    }
}
