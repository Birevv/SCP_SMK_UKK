<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreJournalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        if ($user->hasRole('Super Admin') || $user->role?->name === 'Super Admin') {
            return true;
        }

        // Guru BK murni (tanpa peran Guru/Wali Kelas) tidak diizinkan membuat jurnal mengajar
        $isPureBk = ($user->hasRole('Guru BK') || $user->role?->name === 'Guru BK')
            && ! $user->hasAnyRole(['Guru', 'Wali Kelas'])
            && ! in_array($user->role?->name, ['Guru', 'Wali Kelas'], true);

        if ($isPureBk) {
            return false;
        }

        return $user->can('Create:Journal')
            || $user->hasAnyRole(['Guru', 'Wali Kelas'])
            || in_array($user->role?->name, ['Guru', 'Wali Kelas'], true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'schedule_id' => [
                'required',
                'integer',
                'exists:schedules,id',
                Rule::unique('journals', 'schedule_id')->where(function ($query) {
                    return $query->whereDate('date', $this->input('date'));
                }),
            ],
            'date' => ['required', 'date'],
            'teaching_material' => ['required', 'string', 'max:1000'],
            'notes' => ['required', 'string', 'max:500'],
            'activity_photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:3072'],
            'attendances' => ['required', 'array', 'min:1'],
            'attendances.*.student_id' => ['required', 'integer', 'exists:users,id'],
            'attendances.*.status' => ['required', 'in:Hadir,Izin,Sakit,Alpa'],
            'attendances.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'schedule_id.unique' => 'Jurnal untuk jadwal mengajar ini pada tanggal yang dipilih sudah pernah diisi. Setiap jadwal hanya dapat diisi 1 kali per tanggal pembelajaran.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi Input Jurnal Dan Presensi Gagal.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
