<?php

namespace Database\Factories;

use App\Models\Lowongan;
use App\Models\Perusahaan;
use Illuminate\Database\Eloquent\Factories\Factory;

class LowonganFactory extends Factory
{
    protected $model = Lowongan::class;

    public function definition(): array
    {
        return [
            'judul_lowongan' => fake()->jobTitle() . ' - ' . fake()->company(),
            'deskripsi' => fake()->paragraphs(3, true),
            'tipe_pekerjaan' => fake()->randomElement(['Full-time', 'Part-time', 'Contract', 'Internship']),
            'lokasi' => fake()->city(),
            'status' => fake()->randomElement(['draft', 'published', 'closed']),
            'approval_status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'lowongan_selesai' => fake()->date('Y-m-d'),
            'id_pekerjaan' => null,
            'foto_lowongan' => null,
            'id_perusahaan' => Perusahaan::factory(),
            'id_users' => null,
        ];
    }
}
