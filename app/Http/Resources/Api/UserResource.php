<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $roleName = $this->role?->name;
        $isBK = $this->hasRole('Guru BK') || $roleName === 'Guru BK';
        $isWali = $this->hasRole('Wali Kelas') || $roleName === 'Wali Kelas';

        if ($isBK) {
            $assignedClassrooms = $this->classrooms;
            $classroomLabel = $this->classrooms->pluck('name')->join(', ');
        } elseif ($isWali) {
            $singleClass = $this->classroom ?? $this->classrooms->first();
            $assignedClassrooms = $singleClass ? collect([$singleClass]) : collect();
            $classroomLabel = $singleClass?->name ?? '';
        } else {
            $assignedClassrooms = $this->classrooms;
            $classroomLabel = $this->classrooms->pluck('name')->join(', ');
        }

        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'identity_number' => $this->identity_number,
            'nisn' => $this->identity_number,
            'nip' => $this->identity_number,
            'nisn_nip' => $this->identity_number,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'profile_photo_url' => $this->profile_photo_url,
            'role' => $this->role?->name,
            'role_badge' => $this->role?->name,
            'classroom' => $classroomLabel,
            'classrooms' => $assignedClassrooms->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
            ])->values(),
            'assignment' => $this->assignment,
            'penugasan' => $this->assignment,
        ];
    }
}
