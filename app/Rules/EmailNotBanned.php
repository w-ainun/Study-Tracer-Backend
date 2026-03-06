<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\User;

class EmailNotBanned implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Cari user dengan email tersebut
        $user = User::where('email_users', $value)
            ->whereHas('alumni', function ($query) {
                $query->where('status_create', 'banned');
            })
            ->first();

        // Jika ada user dengan alumni yang di-banned, gagalkan validasi
        if ($user) {
            $fail('Email ini telah dibanned dan tidak dapat digunakan untuk mendaftar.');
        }
    }
}
