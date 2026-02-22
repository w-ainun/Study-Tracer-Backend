<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use Illuminate\Database\Seeder;

class JurusanSeeder extends Seeder
{
    public function run(): void
    {
        $jurusanList = [
            'Teknik Komputer dan Jaringan',
            'Rekayasa Perangkat Lunak',
            'Multimedia',
            'Akuntansi',
            'Administrasi Perkantoran',
            'Pemasaran',
            'Teknik Kendaraan Ringan',
            'Teknik Elektronika Industri',
        ];

        foreach ($jurusanList as $jurusan) {
            Jurusan::create(['nama_jurusan' => $jurusan]);
        }
    }
}
