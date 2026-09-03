<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date?->format('Y-m-d'),
            'status' => $this->status,
            'notes' => $this->notes,
            'subject_name' => $this->journal?->schedule?->subject?->name,
            'teacher_name' => $this->journal?->schedule?->teacher?->full_name,
            'classroom_name' => $this->journal?->schedule?->classroom?->name,
            'start_time' => $this->journal?->schedule?->start_time,
            'end_time' => $this->journal?->schedule?->end_time,
        ];
    }
}
