<?php

namespace Database\Seeders;

use App\Models\Kota;
use App\Models\Provinsi;
use Illuminate\Database\Seeder;

class KotaSeeder extends Seeder
{
    public function run(): void
    {
        $kotaData = [
            'DKI Jakarta' => ['Jakarta Pusat', 'Jakarta Utara', 'Jakarta Barat', 'Jakarta Selatan', 'Jakarta Timur'],
            'Jawa Barat' => ['Bandung', 'Bekasi', 'Bogor', 'Depok', 'Cimahi', 'Tasikmalaya', 'Sukabumi'],
            'Jawa Tengah' => ['Semarang', 'Solo', 'Magelang', 'Pekalongan', 'Salatiga'],
            'Jawa Timur' => ['Surabaya', 'Malang', 'Kediri', 'Mojokerto', 'Madiun', 'Blitar'],
            'DI Yogyakarta' => ['Yogyakarta', 'Sleman', 'Bantul', 'Gunung Kidul', 'Kulon Progo'],
            'Banten' => ['Tangerang', 'Tangerang Selatan', 'Serang', 'Cilegon'],
            'Bali' => ['Denpasar', 'Badung', 'Gianyar', 'Tabanan'],
        ];

        foreach ($kotaData as $namaProvinsi => $kotaList) {
            $provinsi = Provinsi::where('nama_provinsi', $namaProvinsi)->first();
            if ($provinsi) {
                foreach ($kotaList as $kota) {
                    Kota::create([
                        'nama_kota' => $kota,
                        'id_provinsi' => $provinsi->id_provinsi,
                    ]);
                }
            }
        }
    }
}
