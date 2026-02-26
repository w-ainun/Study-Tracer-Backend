<?php

namespace Database\Factories;

use App\Models\Universitas;
use App\Models\JurusanKuliah;
use App\Models\RiwayatStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class UniversitasFactory extends Factory
{
    protected $model = Universitas::class;

    public function definition(): array
    {
        $universitasList = [
            'Universitas Indonesia',
            'Institut Teknologi Bandung',
            'Universitas Gadjah Mada',
            'Universitas Brawijaya',
            'Universitas Diponegoro',
            'Universitas Airlangga',
            'Institut Teknologi Sepuluh Nopember',
            'Universitas Padjadjaran',
        ];

        return [
            'nama_universitas' => fake()->randomElement($universitasList),
            'id_jurusanKuliah' => JurusanKuliah::factory(),
            'jalur_masuk' => fake()->randomElement(['SNBP', 'SNBT', 'Mandiri', 'Beasiswa', 'lainnya']),
            'id_riwayat' => RiwayatStatus::factory(),
            'jenjang' => fake()->randomElement(['D3', 'D4', 'S1', 'S2', 'S3']),
        ];
    }

    /**
     * Admin-created entry (name only, no FK relations).
     */
    public function adminEntry(): static
    {
        return $this->state(fn (array $attributes) => [
            'id_jurusanKuliah' => null,
            'jalur_masuk' => null,
            'id_riwayat' => null,
            'jenjang' => null,
        ]);
    }
}
