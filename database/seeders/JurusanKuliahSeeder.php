<?php

namespace Database\Seeders;

use App\Models\JurusanKuliah;
use App\Models\Universitas;
use Illuminate\Database\Seeder;

class JurusanKuliahSeeder extends Seeder
{
    public function run(): void
    {
        $jurusanList = [
            'Teknik Informatika',
            'Sistem Informasi',
            'Ilmu Komputer',
            'Manajemen',
            'Akuntansi',
            'Hukum',
            'Kedokteran',
            'Teknik Sipil',
            'Teknik Elektro',
            'Psikologi',
            'Pendidikan Guru',
            'Farmasi',
        ];

        $univIds = Universitas::pluck('id_universitas')->toArray();

        foreach ($jurusanList as $jurusan) {
            JurusanKuliah::create([
                'nama_jurusan' => $jurusan,
                'id_universitas' => !empty($univIds) ? fake()->randomElement($univIds) : null,
            ]);
        }
    }
}
