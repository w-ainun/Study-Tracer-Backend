<?php

namespace Database\Factories;

use App\Models\PertanyaanKuesioner;
use App\Models\Kuesioner;
use Illuminate\Database\Eloquent\Factories\Factory;

class PertanyaanKuesionerFactory extends Factory
{
    protected $model = PertanyaanKuesioner::class;

    public function definition(): array
    {
        return [
            'id_kuesioner' => Kuesioner::factory(),
            'pertanyaan' => fake()->sentence() . '?',
            'tipe_pertanyaan' => fake()->randomElement(['pilihan_tunggal', 'pilihan_ganda', 'teks_pendek', 'skala']),
            'status_pertanyaan' => fake()->randomElement(['TERLIHAT', 'TERSEMBUNYI', 'DRAF']),
            'kategori' => fake()->optional(0.5)->randomElement(['Kepuasan', 'Relevansi', 'Fasilitas', 'Umum']),
            'judul_bagian' => fake()->optional(0.3)->sentence(3),
            'urutan' => fake()->numberBetween(0, 20),
        ];
    }
}
