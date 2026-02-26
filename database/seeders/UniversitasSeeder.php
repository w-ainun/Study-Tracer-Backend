<?php

namespace Database\Seeders;

use App\Models\Universitas;
use Illuminate\Database\Seeder;

class UniversitasSeeder extends Seeder
{
    public function run(): void
    {
        $universitasList = [
            'Universitas Indonesia',
            'Institut Teknologi Bandung',
            'Universitas Gadjah Mada',
            'Universitas Brawijaya',
            'Universitas Diponegoro',
            'Universitas Airlangga',
            'Institut Teknologi Sepuluh Nopember',
            'Universitas Padjadjaran',
            'Universitas Sebelas Maret',
            'Universitas Hasanuddin',
        ];

        foreach ($universitasList as $nama) {
            Universitas::create(['nama_universitas' => $nama]);
        }
    }
}
