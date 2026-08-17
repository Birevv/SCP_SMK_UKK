<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponser;

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        
        $user = User::with(['role', 'classroom'])
            ->where('username', $credentials['username'])
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
        $user = $request->user()->load(['role', 'classroom']);
        
        return $this->successResponse(new UserResource($user), 'Data Profile Berhasil Diambil');    

    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logout Berhasil, Token Telah Dicabut');
    }
}
