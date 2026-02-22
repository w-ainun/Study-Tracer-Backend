<?php

namespace Database\Factories;

use App\Models\RiwayatStatus;
use App\Models\Alumni;
use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

class RiwayatStatusFactory extends Factory
{
    protected $model = RiwayatStatus::class;

    public function definition(): array
    {
        $tahunMulai = fake()->year();

        return [
            'id_alumni' => Alumni::factory(),
            'id_status' => Status::factory(),
            'tahun_mulai' => $tahunMulai,
            'tahun_selesai' => fake()->optional()->year(),
        ];
    }
}
