<?php

namespace Database\Seeders;

use App\Models\Alumni;
use App\Models\RiwayatStatus;
use App\Models\Status;
use App\Models\Pekerjaan;
use App\Models\Kuliah;
use App\Models\Universitas;
use App\Models\Wirausaha;
use App\Models\Perusahaan;
use App\Models\Kota;
use App\Models\JurusanKuliah;
use App\Models\BidangUsaha;
use Illuminate\Database\Seeder;

class RiwayatStatusSeeder extends Seeder
{
    public function run(): void
    {
        $alumniList = Alumni::all();
        $statusBekerja = Status::where('nama_status', 'Bekerja')->first();
        $statusKuliah = Status::where('nama_status', 'Kuliah')->first();
        $statusWirausaha = Status::where('nama_status', 'Wirausaha')->first();
        $kotaIds = Kota::pluck('id_kota')->toArray();
        $univIds = Universitas::pluck('id_universitas')->toArray();
        $jurusanKuliahIds = JurusanKuliah::pluck('id_jurusanKuliah')->toArray();
        $bidangIds = BidangUsaha::pluck('id_bidang')->toArray();

        foreach ($alumniList as $alumni) {
            $statusType = fake()->randomElement(['bekerja', 'kuliah', 'wirausaha']);

            if ($statusType === 'bekerja' && $statusBekerja) {
                $riwayat = RiwayatStatus::create([
                    'id_alumni' => $alumni->id_alumni,
                    'id_status' => $statusBekerja->id_status,
                    'tahun_mulai' => fake()->numberBetween(2022, 2025),
                    'tahun_selesai' => null,
                ]);

                $perusahaan = Perusahaan::create([
                    'nama_perusahaan' => fake()->company(),
                    'id_kota' => fake()->randomElement($kotaIds),
                    'jalan' => fake()->streetAddress(),
                ]);

                Pekerjaan::create([
                    'posisi' => fake()->jobTitle(),
                    'id_perusahaan' => $perusahaan->id_perusahaan,
                    'id_riwayat' => $riwayat->id_riwayat,
                ]);
            } elseif ($statusType === 'kuliah' && $statusKuliah) {
                $riwayat = RiwayatStatus::create([
                    'id_alumni' => $alumni->id_alumni,
                    'id_status' => $statusKuliah->id_status,
                    'tahun_mulai' => fake()->numberBetween(2022, 2025),
                    'tahun_selesai' => null,
                ]);

                Kuliah::create([
                    'id_universitas' => fake()->randomElement($univIds),
                    'id_jurusanKuliah' => fake()->randomElement($jurusanKuliahIds),
                    'jalur_masuk' => fake()->randomElement(['SNBP', 'SNBT', 'Mandiri', 'Beasiswa', 'lainnya']),
                    'jenjang' => fake()->randomElement(['D3', 'D4', 'S1', 'S2', 'S3']),
                    'id_riwayat' => $riwayat->id_riwayat,
                ]);
            } elseif ($statusType === 'wirausaha' && $statusWirausaha) {
                $riwayat = RiwayatStatus::create([
                    'id_alumni' => $alumni->id_alumni,
                    'id_status' => $statusWirausaha->id_status,
                    'tahun_mulai' => fake()->numberBetween(2022, 2025),
                    'tahun_selesai' => null,
                ]);

                Wirausaha::create([
                    'id_bidang' => fake()->randomElement($bidangIds),
                    'nama_usaha' => fake()->company(),
                    'id_riwayat' => $riwayat->id_riwayat,
                ]);
            }
        }
    }
}
