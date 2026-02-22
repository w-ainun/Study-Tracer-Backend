<?php

namespace App\Repositories;

use App\Interfaces\AuthRepositoryInterface;
use App\Models\User;
use App\Models\Alumni;

class AuthRepository implements AuthRepositoryInterface
{
    public function createUser(array $data)
    {
        return User::create([
            'email_users' => $data['email'],
            'password' => $data['password'],
            'role' => 'alumni',
        ]);
    }

    public function createAlumniProfile(int $userId, array $data)
    {
        return Alumni::create(array_merge($data, ['id_users' => $userId]));
    }

    public function findUserByEmail(string $email)
    {
        return User::where('email_users', $email)->first();
    }

    public function findUserById(int $id)
    {
        return User::with(['alumni.jurusan', 'alumni.skills', 'alumni.socialMedia', 'admin'])
            ->find($id);
    }
}
