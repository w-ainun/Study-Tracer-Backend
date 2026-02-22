<?php

namespace Database\Factories;

use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

class StatusFactory extends Factory
{
    protected $model = Status::class;

    public function definition(): array
    {
        $statusList = ['Bekerja', 'Kuliah', 'Wirausaha', 'Belum Bekerja'];

        return [
            'nama_status' => fake()->randomElement($statusList),
        ];
    }
}
