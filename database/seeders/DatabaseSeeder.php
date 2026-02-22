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
            ProvinsiSeeder::class,
            KotaSeeder::class,
            JurusanSeeder::class,
            JurusanKuliahSeeder::class,
            SkillSeeder::class,
            SocialMediaSeeder::class,
            StatusSeeder::class,
            BidangUsahaSeeder::class,
            UserSeeder::class,
            RiwayatStatusSeeder::class,
            LowonganSeeder::class,
            KuesionerSeeder::class,
        ]);
    }
}
