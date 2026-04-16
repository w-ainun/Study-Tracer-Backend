<?php

namespace App\Console\Commands;

use App\Models\Kota;
use App\Models\Perusahaan;
use App\Models\Provinsi;
use App\Models\Universitas;
use App\Models\Wirausaha;
use App\Services\GeocodingService;
use Illuminate\Console\Command;

/**
 * Batch geocode semua entity yang belum punya koordinat.
 *
 * Usage:
 *   php artisan geocode:addresses              # Geocode semua tipe
 *   php artisan geocode:addresses --type=perusahaan   # Hanya perusahaan
 *   php artisan geocode:addresses --type=universitas  # Hanya universitas
 *   php artisan geocode:addresses --type=kota         # Hanya kota
 *   php artisan geocode:addresses --type=wirausaha    # Hanya wirausaha
 *   php artisan geocode:addresses --force       # Re-geocode yang sudah punya koordinat juga
 *   php artisan geocode:addresses --limit=50    # Geocode max 50 record
 */
class GeocodeAddresses extends Command
{
    protected $signature = 'geocode:addresses
                            {--type= : Tipe entity: perusahaan, universitas, kota, wirausaha, atau kosong untuk semua}
                            {--force : Re-geocode termasuk yang sudah punya koordinat}
                            {--limit=0 : Limit jumlah record (0 = tanpa limit)}';

    protected $description = 'Geocode alamat entity ke koordinat lat/lng menggunakan OpenStreetMap Nominatim API';

    private GeocodingService $geocoding;

    public function __construct(GeocodingService $geocoding)
    {
        parent::__construct();
        $this->geocoding = $geocoding;
    }

    public function handle(): int
    {
        // Prevent timeout during long-running geocoding process
        set_time_limit(0);
        ini_set('memory_limit', '1G');

        $type = $this->option('type');
        $force = $this->option('force');
        $limit = (int) $this->option('limit');

        $this->info('🌍 Geocoding Alamat → Koordinat (via Nominatim/OpenStreetMap)');
        $this->info('   Rate limit: ~1 request/detik (Nominatim policy)');
        $this->newLine();

        // 1. Diagnostic: Check Internet
        $this->comment('🔍 Melakukan diagnosa sistem...');
        try {
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()->get('https://nominatim.openstreetmap.org/status.php');
            if (!$response->successful()) {
                $this->error('❌ Server Nominatim sedang bermasalah atau internet Anda terganggu.');
                return Command::FAILURE;
            }
            $this->info('  ✅ Koneksi internet & Nominatim: Oke');
        } catch (\Exception $e) {
            $this->error('❌ Gagal terhubung ke internet: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // 2. Diagnostic: Check Cache/Redis
        try {
            \Illuminate\Support\Facades\Cache::put('geocode_check', true, 10);
            $this->info('  ✅ Koneksi Cache (' . config('cache.default') . '): Oke');
        } catch (\Exception $e) {
            $this->error('❌ Gagal terhubung ke Cache (' . config('cache.default') . ').');
            $this->comment('   Saran: Jika pakai Redis, pastikan Redis jalan. Atau ubah CACHE_STORE=file di .env');
            $this->error('   Error: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // 3. Diagnostic: Check Database Data
        $counts = [
            'kota' => \App\Models\Kota::count(),
            'perusahaan' => \App\Models\Perusahaan::count(),
            'universitas' => \App\Models\Universitas::count(),
            'wirausaha' => \App\Models\Wirausaha::count(),
        ];

        if (array_sum($counts) === 0) {
            $this->warning('❌ Database Anda masih KOSONG.');
            $this->info('   Saran: Jalankan "php artisan db:seed" terlebih dahulu agar ada data yang bisa di-geocode.');
            return Command::FAILURE;
        }
        $this->info('  ✅ Data ditemukan: ' . implode(', ', array_map(fn($k, $v) => "$v $k", array_keys($counts), array_values($counts))));
        $this->newLine();

        $stats = ['success' => 0, 'failed' => 0, 'skipped' => 0];

        if (!$type || $type === 'kota') {
            $this->geocodeKota($force, $limit, $stats);
        }
        if (!$type || $type === 'perusahaan') {
            $this->geocodePerusahaan($force, $limit, $stats);
        }
        if (!$type || $type === 'universitas') {
            $this->geocodeUniversitas($force, $limit, $stats);
        }
        if (!$type || $type === 'wirausaha') {
            $this->geocodeWirausaha($force, $limit, $stats);
        }

        $this->newLine();
        $this->info("✅ Selesai!");
        $this->table(
            ['Status', 'Jumlah'],
            [
                ['Berhasil', $stats['success']],
                ['Gagal', $stats['failed']],
                ['Dilewati', $stats['skipped']],
            ]
        );

        return Command::SUCCESS;
    }

    private function geocodeKota(bool $force, int $limit, array &$stats): void
    {
        $query = Kota::with('provinsi');
        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('latitude')->orWhereNull('longitude');
            });
        }
        if ($limit > 0) $query->limit($limit);

        $items = $query->get();
        if ($items->isEmpty()) {
            $this->line("  [Kota] Semua sudah punya koordinat ✓");
            return;
        }

        $this->info("  [Kota] Memproses {$items->count()} record...");
        $bar = $this->output->createProgressBar($items->count());

        foreach ($items as $kota) {
            $success = $this->geocoding->geocodeKota($kota);
            if ($success) {
                $stats['success']++;
            } else {
                $stats['failed']++;
                $this->line("    ⚠ Gagal: {$kota->nama_kota}");
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
    }

    private function geocodePerusahaan(bool $force, int $limit, array &$stats): void
    {
        $query = Perusahaan::with('kota.provinsi');
        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('latitude')->orWhereNull('longitude');
            });
        }
        if ($limit > 0) $query->limit($limit);

        $items = $query->get();
        if ($items->isEmpty()) {
            $this->line("  [Perusahaan] Semua sudah punya koordinat ✓");
            return;
        }

        $this->info("  [Perusahaan] Memproses {$items->count()} record...");
        $bar = $this->output->createProgressBar($items->count());

        foreach ($items as $perusahaan) {
            $success = $this->geocoding->geocodePerusahaan($perusahaan);
            if ($success) {
                $stats['success']++;
            } else {
                $stats['failed']++;
                $this->line("    ⚠ Gagal: {$perusahaan->nama_perusahaan}");
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
    }

    private function geocodeUniversitas(bool $force, int $limit, array &$stats): void
    {
        $query = Universitas::query();
        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('latitude')->orWhereNull('longitude');
            });
        }
        if ($limit > 0) $query->limit($limit);

        $items = $query->get();
        if ($items->isEmpty()) {
            $this->line("  [Universitas] Semua sudah punya koordinat ✓");
            return;
        }

        $this->info("  [Universitas] Memproses {$items->count()} record...");
        $bar = $this->output->createProgressBar($items->count());

        foreach ($items as $universitas) {
            $success = $this->geocoding->geocodeUniversitas($universitas);
            if ($success) {
                $stats['success']++;
            } else {
                $stats['failed']++;
                $this->line("    ⚠ Gagal: {$universitas->nama_universitas}");
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
    }

    private function geocodeWirausaha(bool $force, int $limit, array &$stats): void
    {
        $query = Wirausaha::with('bidangUsaha');
        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('latitude')->orWhereNull('longitude');
            });
        }
        if ($limit > 0) $query->limit($limit);

        $items = $query->get();
        if ($items->isEmpty()) {
            $this->line("  [Wirausaha] Semua sudah punya koordinat ✓");
            return;
        }

        $this->info("  [Wirausaha] Memproses {$items->count()} record...");
        $bar = $this->output->createProgressBar($items->count());

        foreach ($items as $wirausaha) {
            $success = $this->geocoding->geocodeWirausaha($wirausaha);
            if ($success) {
                $stats['success']++;
            } else {
                $stats['failed']++;
                $this->line("    ⚠ Gagal: {$wirausaha->nama_usaha}");
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
    }
}
