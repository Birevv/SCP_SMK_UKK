<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChangePasswordRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    use ApiResponser;

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $loginIdentifier = trim($credentials['nisn'] ?? $credentials['identity_number'] ?? $credentials['login'] ?? $credentials['username'] ?? '');

        $user = User::with(['role', 'classrooms'])
            ->where(function ($query) use ($loginIdentifier) {
                $query->where('identity_number', $loginIdentifier)
                    ->orWhere('username', $loginIdentifier);
            })
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return $this->errorResponse('Kredensial Login Tidak Valid', 401);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return $this->successResponse([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 'Login Berhasil');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['role', 'classrooms']);

        return $this->successResponse(new UserResource($user), 'Data Profile Berhasil Diambil');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logout Berhasil, Token Telah Dicabut');
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $data['profile_photo'] = $request->file('profile_photo')->store('profile_photos', 'public');
        }

        $user->update($data);
        $user->load(['role', 'classrooms']);

        return $this->successResponse(new UserResource($user), 'Profile berhasil diperbarui');
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update([
            'password' => $request->validated('new_password'),
        ]);

        return $this->successResponse(null, 'Password berhasil diubah');
    }
}
