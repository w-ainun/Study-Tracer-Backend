<?php

namespace Database\Factories;

use App\Models\BidangUsaha;
use Illuminate\Database\Eloquent\Factories\Factory;

class BidangUsahaFactory extends Factory
{
    protected $model = BidangUsaha::class;

    public function definition(): array
    {
        $bidangList = [
            'Teknologi Informasi',
            'Kuliner',
            'Fashion',
            'Pertanian',
            'Jasa',
            'Perdagangan',
            'Pendidikan',
            'Kesehatan',
        ];

        return [
            'nama_bidang' => fake()->randomElement($bidangList),
        ];
    }
}
