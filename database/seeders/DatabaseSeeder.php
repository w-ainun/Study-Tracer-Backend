<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            JurusanSeeder::class,
            JurusanKuliahSeeder::class,
            SkillSeeder::class,
            SocialMediaSeeder::class,
            StatusSeeder::class,
            BidangUsahaSeeder::class,
            RiwayatStatusSeeder::class,
            LowonganSeeder::class,
            KuesionerSeeder::class,
        ]);
    }
}
