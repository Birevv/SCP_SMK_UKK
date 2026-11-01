<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PasswordResetOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ForgotPasswordOtpController extends Controller
{
    public function __construct(
        protected PasswordResetOtpService $otpService
    ) {}

    /**
     * Request OTP untuk dikirim ke email user.
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
        ]);

        try {
            $this->otpService->sendOtp($validated['email']);

            return response()->json([
                'status' => 'success',
                'message' => 'Kode OTP berhasil dikirim ke alamat email Anda.',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->validator->errors()->first('email') ?? $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengirim email OTP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verifikasi kode OTP dan simpan password baru.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
            'password' => ['required', 'min:8', 'confirmed'],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits' => 'Kode OTP harus tepat 6 digit angka.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        try {
            $this->otpService->resetPassword(
                $validated['email'],
                $validated['otp'],
                $validated['password']
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Kata sandi akun Anda berhasil diperbarui. Silakan login kembali.',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->validator->errors()->first() ?? $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }
}
