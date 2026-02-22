<?php

namespace Database\Seeders;

use App\Models\Provinsi;
use Illuminate\Database\Seeder;

class ProvinsiSeeder extends Seeder
{
    public function run(): void
    {
        $provinsiList = [
            'Aceh', 'Sumatera Utara', 'Sumatera Barat', 'Riau', 'Jambi',
            'Sumatera Selatan', 'Bengkulu', 'Lampung', 'Kepulauan Bangka Belitung',
            'Kepulauan Riau', 'DKI Jakarta', 'Jawa Barat', 'Jawa Tengah',
            'DI Yogyakarta', 'Jawa Timur', 'Banten', 'Bali',
            'Nusa Tenggara Barat', 'Nusa Tenggara Timur', 'Kalimantan Barat',
            'Kalimantan Tengah', 'Kalimantan Selatan', 'Kalimantan Timur',
            'Kalimantan Utara', 'Sulawesi Utara', 'Sulawesi Tengah',
            'Sulawesi Selatan', 'Sulawesi Tenggara', 'Gorontalo',
            'Sulawesi Barat', 'Maluku', 'Maluku Utara', 'Papua',
            'Papua Barat', 'Papua Selatan', 'Papua Tengah', 'Papua Pegunungan',
            'Papua Barat Daya',
        ];

        foreach ($provinsiList as $provinsi) {
            Provinsi::create(['nama_provinsi' => $provinsi]);
        }
    }
}
