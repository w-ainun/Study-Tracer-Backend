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
            'ATPH',
            'TBSM',
            'ATU',
            'APHP',
            'TKR',
            'RPL',
        ];

        return [
            'nama_jurusan' => fake()->randomElement($jurusanList),
        ];
    }
}
