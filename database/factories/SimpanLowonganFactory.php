<?php

namespace Database\Factories;

use App\Models\SimpanLowongan;
use App\Models\User;
use App\Models\Lowongan;
use Illuminate\Database\Eloquent\Factories\Factory;

class SimpanLowonganFactory extends Factory
{
    protected $model = SimpanLowongan::class;

    public function definition(): array
    {
        return [
            'id_user' => User::factory(),
            'id_lowongan' => Lowongan::factory(),
        ];
    }
}
