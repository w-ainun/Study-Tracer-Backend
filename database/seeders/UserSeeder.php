<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Alumni;
use App\Models\Admin;
use App\Models\Jurusan;
use App\Models\AlumniSkill;
use App\Models\AlumniSocialMedia;
use App\Models\Skill;
use App\Models\SocialMedia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $adminUser = User::create([
            'email_users' => 'admin@tracerstudy.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        Admin::create([
            'nama_admin' => 'Administrator',
            'id_users' => $adminUser->id_users,
        ]);

        // Create alumni users
        $jurusanIds = Jurusan::pluck('id_jurusan')->toArray();
        $skillIds = Skill::pluck('id_skills')->toArray();
        $sosmedIds = SocialMedia::pluck('id_sosmed')->toArray();

        for ($i = 1; $i <= 20; $i++) {
            $user = User::create([
                'email_users' => "alumni{$i}@tracerstudy.com",
                'password' => Hash::make('password'),
                'role' => 'alumni',
            ]);

            $alumni = Alumni::create([
                'nama_alumni' => fake()->name(),
                'nis' => fake()->numerify('######'),
                'nisn' => fake()->numerify('##########'),
                'jenis_kelamin' => fake()->randomElement(['Laki-laki', 'Perempuan']),
                'tanggal_lahir' => fake()->date('Y-m-d', '2005-12-31'),
                'tempat_lahir' => fake()->city(),
                'tahun_masuk' => fake()->numberBetween(2018, 2023),
                'foto' => null,
                'alamat' => fake()->address(),
                'no_hp' => fake()->phoneNumber(),
                'id_jurusan' => fake()->randomElement($jurusanIds),
                'tahun_lulus' => fake()->date('Y-m-d', '2025-06-30'),
                'id_users' => $user->id_users,
                'status_create' => 'ok',
            ]);

            // Assign 1-3 random skills
            $randomSkills = fake()->randomElements($skillIds, fake()->numberBetween(1, 3));
            foreach ($randomSkills as $skillId) {
                AlumniSkill::create([
                    'id_alumni' => $alumni->id_alumni,
                    'id_skills' => $skillId,
                ]);
            }

            // Assign 1-2 random social media
            $randomSosmed = fake()->randomElements($sosmedIds, fake()->numberBetween(1, 2));
            foreach ($randomSosmed as $sosmedId) {
                AlumniSocialMedia::create([
                    'id_alumni' => $alumni->id_alumni,
                    'id_sosmed' => $sosmedId,
                    'url' => fake()->url(),
                    'create_at' => now(),
                ]);
            }
        }
    }
}
