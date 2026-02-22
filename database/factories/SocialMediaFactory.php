<?php

namespace Database\Factories;

use App\Models\SocialMedia;
use Illuminate\Database\Eloquent\Factories\Factory;

class SocialMediaFactory extends Factory
{
    protected $model = SocialMedia::class;

    public function definition(): array
    {
        $socialMediaList = [
            ['nama' => 'Instagram', 'icon' => 'instagram'],
            ['nama' => 'LinkedIn', 'icon' => 'linkedin'],
            ['nama' => 'Facebook', 'icon' => 'facebook'],
            ['nama' => 'Twitter', 'icon' => 'twitter'],
            ['nama' => 'GitHub', 'icon' => 'github'],
            ['nama' => 'YouTube', 'icon' => 'youtube'],
        ];

        $selected = fake()->randomElement($socialMediaList);

        return [
            'nama_sosmed' => $selected['nama'],
            'icon_sosmed' => $selected['icon'],
        ];
    }
}
