<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreJournalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'schedule_id' => ['required', 'integer', 'exists:schedules, id'],
            'date' => ['required', 'date'],
            'teaching_material' => ['required', 'string', 'max:1000'],
            'notes' => ['required', 'string', 'max:500'],
            'activity_photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:3072'],
            'attendances' => ['required', 'array', 'min:1'],
            'attendances.*.student_id' => ['required', 'integer', 'exists:users, id'],
            'attendances.*.status' => ['required', 'in:Hadir,Izin,Sakit,Alpa'],
            'attendances.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
