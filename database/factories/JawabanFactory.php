<?php

namespace Database\Factories;

use App\Models\Jawaban;
use App\Models\Pertanyaan;
use App\Models\User;
use App\Models\OpsiJawaban;
use Illuminate\Database\Eloquent\Factories\Factory;

class JawabanFactory extends Factory
{
    protected $model = Jawaban::class;

    public function definition(): array
    {
        return [
            'id_pertanyaan' => Pertanyaan::factory(),
            'id_user' => User::factory(),
            'id_opsiJawaban' => OpsiJawaban::factory(),
            'jawaban' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(['Selesai', 'Belum Selesai']),
        ];
    }

    /**
     * Set status to Selesai
     */
    public function selesai(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Selesai',
        ]);
    }

    /**
     * Set status to Belum Selesai
     */
    public function belumSelesai(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Belum Selesai',
        ]);
    }
}
