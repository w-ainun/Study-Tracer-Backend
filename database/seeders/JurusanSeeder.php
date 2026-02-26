<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use Illuminate\Database\Seeder;

class JurusanSeeder extends Seeder
{
    public function run(): void
    {
        $jurusanList = [
            'TKJ',
            'RPL',
            'ATPH',
            'TBSM',
            'ATU',
            'APHP',
            'TKR',
        ];

        foreach ($jurusanList as $jurusan) {
            Jurusan::create(['nama_jurusan' => $jurusan]);
        }
    }
}
