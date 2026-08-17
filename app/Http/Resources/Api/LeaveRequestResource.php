<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class LeaveRequestResource extends JsonResource
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
            'reason_type' => $this->reason_type,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'description' => $this->description,
            'attachment_url' => $this->attachment_photo ? Storage::url($this->attacment_photo) : null,
            'status' => $this->status,
            'verified_by' => $this->verifiedBy?->full_name,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
