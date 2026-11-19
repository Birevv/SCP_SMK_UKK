<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateJournalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        if ($user->hasRole('Super Admin') || $user->role?->name === 'Super Admin') {
            return true;
        }

        $isPureBk = ($user->hasRole('Guru BK') || $user->role?->name === 'Guru BK')
            && ! $user->hasAnyRole(['Guru', 'Wali Kelas'])
            && ! in_array($user->role?->name, ['Guru', 'Wali Kelas'], true);

        if ($isPureBk) {
            return false;
        }

        return $user->can('Update:Journal')
            || $user->hasAnyRole(['Guru', 'Wali Kelas'])
            || in_array($user->role?->name, ['Guru', 'Wali Kelas'], true);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'teaching_material' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:500'],
            'activity_photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'attendances' => ['sometimes', 'array', 'min:1'],
            'attendances.*.student_id' => ['required_with:attendances', 'integer', 'exists:users,id'],
            'attendances.*.status' => ['required_with:attendances', 'in:Hadir,Izin,Sakit,Alpa'],
            'attendances.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'teaching_material.required' => 'Materi pembelajaran tidak boleh kosong.',
            'activity_photo.image' => 'Bukti foto kegiatan harus berupa file gambar.',
            'activity_photo.mimes' => 'Format foto kegiatan harus JPG, JPEG, atau PNG.',
            'activity_photo.max' => 'Ukuran foto kegiatan maksimal 5 MB.',
            'attendances.*.status.in' => 'Status kehadiran harus Hadir, Izin, Sakit, atau Alpa.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $firstError = $validator->errors()->first();

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $firstError ?: 'Validasi Pembaruan Jurnal Dan Presensi Gagal.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
