<?php

namespace Database\Seeders;

use App\Models\Provinsi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class ProvinsiSeeder extends Seeder
{
    public function run(): void
    {
        // Disable SSL verification untuk development
        $response = Http::withOptions([
            'verify' => false
        ])->get('https://wilayah.id/api/provinces.json');

        if ($response->failed()) {
            $this->command->error('Failed to fetch provinsi data from wilayah.id API');
            return;
        }

        $provinsis = $response->json('data');

        foreach ($provinsis as $p) {
            Provinsi::updateOrCreate(
                ['code' => $p['code']],
                ['nama_provinsi' => $p['name']]
            );
        }

        $this->command->info('Imported ' . count($provinsis) . ' provinsi from API');
    }
}
