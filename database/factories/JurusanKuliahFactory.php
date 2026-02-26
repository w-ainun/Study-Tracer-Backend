<?php

namespace Database\Factories;

use App\Models\JurusanKuliah;
use App\Models\Universitas;
use Illuminate\Database\Eloquent\Factories\Factory;

class JurusanKuliahFactory extends Factory
{
    protected $model = JurusanKuliah::class;

    public function definition(): array
    {
        $jurusanList = [
            'Teknik Informatika',
            'Sistem Informasi',
            'Ilmu Komputer',
            'Manajemen',
            'Akuntansi',
            'Hukum',
            'Kedokteran',
            'Teknik Sipil',
            'Teknik Elektro',
            'Psikologi',
        ];

        return [
            'nama_jurusan' => fake()->randomElement($jurusanList),
            'id_universitas' => Universitas::factory(),
        ];
    }

    /**
     * Jurusan without universitas (standalone master data).
     */
    public function standalone(): static
    {
        return $this->state(fn (array $attributes) => [
            'id_universitas' => null,
        ]);
    }
}
