<?php

namespace Database\Seeders;

use App\Models\BidangUsaha;
use Illuminate\Database\Seeder;

class BidangUsahaSeeder extends Seeder
{
    public function run(): void
    {
        $bidangList = [
            'Teknologi Informasi',
            'Kuliner',
            'Fashion',
            'Pertanian',
            'Jasa',
            'Perdagangan',
            'Pendidikan',
            'Kesehatan',
            'Konstruksi',
            'Transportasi',
        ];

        foreach ($bidangList as $bidang) {
            BidangUsaha::create(['nama_bidang' => $bidang]);
        }
    }
}
