<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\User;

class UniqueEmailExceptRejected implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Cari user dengan email tersebut yang bukan dalam status rejected
        $user = User::where('email_users', $value)
            ->whereHas('alumni', function ($query) {
                // Cek jika status bukan rejected, maka email tidak boleh digunakan
                $query->whereIn('status_create', ['pending', 'ok']);
            })
            ->first();

        // Jika ada user dengan status pending atau ok, gagalkan validasi
        if ($user) {
            $fail('Email ini sudah terdaftar.');
        }
    }
}
