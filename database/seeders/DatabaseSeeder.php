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
            // Master data (no dependencies)
            JurusanSeeder::class,
            SkillSeeder::class,
            SocialMediaSeeder::class,
            StatusSeeder::class,
            BidangUsahaSeeder::class,

            // Geographic data (fetched from wilayah.id API)
            ProvinsiSeeder::class,
            KotaSeeder::class,

            // Universitas master data (before JurusanKuliah)
            UniversitasSeeder::class,
            JurusanKuliahSeeder::class,

            // Users & Alumni (depends on Jurusan, Skills, SocialMedia)
            UserSeeder::class,

            // Career data (depends on Alumni, Status, master data)
            RiwayatStatusSeeder::class,

            // Content (depends on Perusahaan from RiwayatStatus)
            LowonganSeeder::class,
            KuesionerSeeder::class,

            // Jawaban (depends on Kuesioner + Users)
            JawabanSeeder::class,
        ]);
    }
}
