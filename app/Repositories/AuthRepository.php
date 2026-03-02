<?php

namespace App\Repositories;

use App\Interfaces\AuthRepositoryInterface;
use App\Models\User;
use App\Models\Alumni;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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

    public function createPasswordResetToken(string $email, string $token): void
    {
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );
    }

    public function findPasswordResetToken(string $email, string $token)
    {
        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$record) {
            return null;
        }

        // Token expired after 60 minutes
        if (now()->diffInMinutes($record->created_at) > 60) {
            return null;
        }

        if (!Hash::check($token, $record->token)) {
            return null;
        }

        return $record;
    }

    public function deletePasswordResetToken(string $email): void
    {
        DB::table('password_reset_tokens')->where('email', $email)->delete();
    }

    public function updatePassword(int $userId, string $password): void
    {
        User::where('id_users', $userId)->update([
            'password' => Hash::make($password),
        ]);
    }
}
