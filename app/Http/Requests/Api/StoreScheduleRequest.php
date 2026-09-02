<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreScheduleRequest extends FormRequest
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
            'day_of_week' => ['required', 'in:Senin,Selasa,Rabu,Kamis,Jumat'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'classroom_id' => ['required', 'integer', 'exists:classroom,id'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'subject_id' => ['required', 'integer', 'exists:subject_id'],
            'cycle_type' => ['required', 'in:Putaran 1,Putaran 2']
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi Input Jadwal Gagal.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
