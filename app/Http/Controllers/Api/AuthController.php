<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterAlumniRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterAlumniRequest $request)
    {
        try {
            $accountData = $request->only(['email', 'password']);
            $profileData = $request->except(['email', 'password', 'password_confirmation']);

            $token = $this->authService->registerUserAndProfile($accountData, $profileData);

            return $this->createdResponse([
                'token' => $token,
            ], 'Registrasi berhasil');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mendaftar: ' . $e->getMessage());
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $result = $this->authService->login($request->validated());

            return $this->successResponse([
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ], 'Login berhasil');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal login');
        }
    }

    public function me(Request $request)
    {
        try {
            $user = $this->authService->getAuthenticatedUser($request->user());

            return $this->successResponse(
                new UserResource($user->load(['alumni.jurusan', 'alumni.skills', 'alumni.socialMedia', 'alumni.riwayatStatus.status', 'admin']))
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data user');
        }
    }

    public function logout(Request $request)
    {
        try {
            $this->authService->logout($request->user());

            return $this->successResponse(null, 'Logout berhasil');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal logout');
        }
    }
}