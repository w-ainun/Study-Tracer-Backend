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
            
            // Diubah agar tanggal selesai lowongan selalu di masa depan (lebih realistis)
            'lowongan_selesai' => fake()->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
            
            // Kolom waktu baru dari migrasi terakhir
            'jam_mulai' => '08:00:00',
            'jam_berakhir' => '17:00:00',
            
            'id_pekerjaan' => null,
            'foto_lowongan' => null,
            'id_perusahaan' => Perusahaan::factory(),
            'id_users' => null,
        ];
    }
}