<?php

namespace Database\Factories;

use App\Models\JurusanKuliah;
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
        ];
    }
}
