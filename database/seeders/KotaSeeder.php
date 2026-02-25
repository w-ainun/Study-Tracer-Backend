<?php

namespace Database\Seeders;

use App\Models\Kota;
use App\Models\Provinsi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class KotaSeeder extends Seeder
{
    public function run(): void
    {
        $provinsis = Provinsi::all();

        if ($provinsis->isEmpty()) {
            $this->command->error('Provinsi table is empty. Run ProvinsiSeeder first.');
            return;
        }

        $totalKota = 0;

        foreach ($provinsis as $provinsi) {
            $this->command->info("Fetching kota for {$provinsi->nama_provinsi}...");

            $response = Http::get("https://wilayah.id/api/regencies/{$provinsi->code}.json");

            if ($response->failed()) {
                $this->command->warn("Failed to fetch kota for {$provinsi->nama_provinsi}");
                continue;
            }

            $kotas = $response->json('data');

            if (empty($kotas)) {
                continue;
            }

            foreach ($kotas as $k) {
                Kota::updateOrCreate(
                    ['code' => $k['code']],
                    [
                        'nama_kota' => $k['name'],
                        'id_provinsi' => $provinsi->id_provinsi,
                    ]
                );
                $totalKota++;
            }
        }

        $this->command->info("Imported {$totalKota} kota from API");
    }
}
