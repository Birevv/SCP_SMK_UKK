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
        return [
            'id' => $this->id,
            'date' => $this->date?->format('Y-m-d'),
            'subject_name' => $this->schedule?->subject?->name,
            'classroom_name' => $this->schedule?->classroom?->name,
            'room_name' => $this->schedule?->room?->name,
            'cycle_type' => $this->schedule?->cycle_type,
            'teaching_material' => $this->teaching_material,
            'notes' => $this->notes,
            'activity_photo_url' => $this->activity_photo ? Storage::url($this->activity_photo) : null,
            'attendance_count' => $this->attendances_count ?? $this->attendances->count(),
            'present_count' => $this->attendances->where('status', 'Hadir')->count(),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
