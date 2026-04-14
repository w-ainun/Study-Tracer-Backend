<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::updateOrCreate(
            ['email_users' => 'hummatech@tracerstudy.com'],
            [
                'password' => Hash::make('hummapass'),
                'role' => 'admin',
            ]
        );

        Admin::updateOrCreate(
            ['id_users' => $adminUser->id_users],
            ['nama_admin' => 'Hummatech Admin']
        );
    }
}
