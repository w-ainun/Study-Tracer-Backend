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
            'TKJ',
            'RPL',
            'Multimedia',
            'Akuntansi',
            'AP',
            'Pemasaran',
            'TKR',
            'TEI',
        ];

        return [
            'nama_jurusan' => fake()->randomElement($jurusanList),
        ];
    }
}
