<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class JournalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $startTime = $this->schedule?->start_time;
        $endTime = $this->schedule?->end_time;
        $timeSlot = ($startTime && $endTime)
            ? substr((string) $startTime, 0, 5).' - '.substr((string) $endTime, 0, 5).' WIB'
            : null;

        $includeAttendances = $this->relationLoaded('attendances')
            && ($this->attendances->isEmpty() || $this->attendances->first()->relationLoaded('student'));

        return [
            'id' => $this->id,
            'schedule_id' => $this->schedule_id,
            'date' => $this->date?->format('Y-m-d'),
            'time_slot' => $timeSlot,
            'subject_name' => $this->schedule?->subject?->name,
            'classroom_name' => $this->schedule?->classroom?->name,
            'room_name' => $this->schedule?->room?->name,
            'cycle_type' => $this->schedule?->cycle_type,
            'teaching_material' => $this->teaching_material,
            'notes' => $this->notes,
            'activity_photo_url' => $this->activity_photo ? Storage::url($this->activity_photo) : null,
            'attendance_count' => $this->attendances_count ?? $this->attendances->count(),
            'present_count' => $this->attendances->where('status', 'Hadir')->count(),
            'permission_count' => $this->attendances->where('status', 'Izin')->count(),
            'sick_count' => $this->attendances->where('status', 'Sakit')->count(),
            'absent_count' => $this->attendances->where('status', 'Alpa')->count(),
            'attendances' => $this->when($includeAttendances, fn () => $this->attendances
                ->sortBy(fn ($att) => strtolower((string) ($att->student?->full_name ?? '')))
                ->values()
                ->map(fn ($att) => [
                    'id' => $att->id,
                    'student_id' => $att->student_id,
                    'student_name' => $att->student?->full_name ?? '-',
                    'identity_number' => $att->student?->identity_number ?? '-',
                    'status' => $att->status,
                    'notes' => $att->notes,
                ])),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
