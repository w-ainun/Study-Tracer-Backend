<?php

namespace App\Interfaces;

interface AuthRepositoryInterface
{
    public function createUser(array $data);
    public function createAlumniProfile(int $userId, array $profileData);
    public function findUserByEmail(string $email);
    public function findUserById(int $id);
}