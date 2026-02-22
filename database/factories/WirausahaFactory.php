<?php

namespace Database\Factories;

use App\Models\Wirausaha;
use App\Models\BidangUsaha;
use App\Models\RiwayatStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class WirausahaFactory extends Factory
{
    protected $model = Wirausaha::class;

    public function definition(): array
    {
        return [
            'id_bidang' => BidangUsaha::factory(),
            'nama_usaha' => fake()->company(),
            'id_riwayat' => RiwayatStatus::factory(),
        ];
    }
}
