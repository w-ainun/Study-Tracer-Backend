<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'id_users';

    protected $fillable = [
        'email_users',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function alumni()
    {
        return $this->hasOne(Alumni::class, 'id_users', 'id_users');
    }

    public function admin()
    {
        return $this->hasOne(Admin::class, 'id_users', 'id_users');
    }

    public function simpanLowongan()
    {
        return $this->hasMany(SimpanLowongan::class, 'id_user', 'id_users');
    }

    public function jawabanKuesioner()
    {
        return $this->hasMany(JawabanKuesioner::class, 'id_user', 'id_users');
    }
}
