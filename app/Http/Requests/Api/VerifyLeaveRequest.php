<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class VerifyLeaveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('notes') && ! $this->has('rejection_note')) {
            $this->merge([
                'rejection_note' => $this->input('notes'),
            ]);
        } elseif ($this->has('rejection_note') && ! $this->has('notes')) {
            $this->merge([
                'notes' => $this->input('rejection_note'),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:Approved,Rejected'],
            'rejection_note' => ['required_if:status,Rejected', 'nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_note.required_if' => 'Keterangan reject wajib diisi saat menolak pengajuan izin.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi Verifikasi Izin Gagal.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
