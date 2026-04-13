<?php

namespace App\Interfaces;

interface AuthRepositoryInterface
{
    public function createUser(array $data);
    public function createAlumniProfile(int $userId, array $profileData);
    public function findUserByEmail(string $email);
    public function findUserById(int $id);
    public function findUserByGoogleId(string $googleId);
    public function deleteRejectedUserByEmail(string $email): void;
    public function createPasswordResetToken(string $email, string $token): void;
    public function findPasswordResetToken(string $email, string $token);
    public function deletePasswordResetToken(string $email): void;
    public function updatePassword(int $userId, string $password): void;
}