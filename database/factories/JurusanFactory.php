<?php

namespace Database\Factories;

use App\Models\Jurusan;
use Illuminate\Database\Eloquent\Factories\Factory;

class JurusanFactory extends Factory
{
    protected $model = Jurusan::class;

    public function definition(): array
    {
        $jurusanList = [
            'Teknik Komputer dan Jaringan',
            'Rekayasa Perangkat Lunak',
            'Multimedia',
            'Akuntansi',
            'Administrasi Perkantoran',
            'Pemasaran',
            'Teknik Kendaraan Ringan',
            'Teknik Elektronika Industri',
        ];

        return [
            'nama_jurusan' => fake()->randomElement($jurusanList),
        ];
    }
}
