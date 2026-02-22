<?php

namespace App\Services;

use App\Interfaces\AuthRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    private AuthRepositoryInterface $authRepository;

    public function __construct(AuthRepositoryInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    public function registerUserAndProfile(array $accountData, array $profileData)
    {
        return DB::transaction(function () use ($accountData, $profileData) {
            $user = $this->authRepository->createUser($accountData);
            $this->authRepository->createAlumniProfile($user->id_users, $profileData);

            return $user->createToken('auth_token')->plainTextToken;
        });
    }

    public function login(array $credentials)
    {
        $user = $this->authRepository->findUserByEmail($credentials['email']);

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user->load(['alumni.jurusan', 'admin']),
            'token' => $token,
        ];
    }

    public function logout($user)
    {
        $user->currentAccessToken()->delete();
    }

    public function getAuthenticatedUser($user)
    {
        return $this->authRepository->findUserById($user->id_users);
    }
}