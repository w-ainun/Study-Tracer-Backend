<?php

namespace Database\Factories;

use App\Models\AlumniSocialMedia;
use App\Models\Alumni;
use App\Models\SocialMedia;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlumniSocialMediaFactory extends Factory
{
    protected $model = AlumniSocialMedia::class;

    public function definition(): array
    {
        return [
            'id_alumni' => Alumni::factory(),
            'id_sosmed' => SocialMedia::factory(),
            'url' => fake()->url(),
            'create_at' => fake()->dateTime(),
        ];
    }
}
