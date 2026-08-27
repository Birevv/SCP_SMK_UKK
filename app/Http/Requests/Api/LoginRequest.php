<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Override;

class LoginRequest extends FormRequest
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
            'login' => ['required_without_all:username,nisn,identity_number', 'nullable', 'string'],
            'nisn' => ['required_without_all:login,username,identity_number', 'nullable', 'string'],
            'identity_number' => ['required_without_all:login,username,nisn', 'nullable', 'string'],
            'username' => ['required_without_all:login,nisn,identity_number', 'nullable', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    #[Override]
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi Kredensial Login Gagal.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
