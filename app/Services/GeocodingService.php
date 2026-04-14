<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Geocoding Service using Nominatim (OpenStreetMap) API.
 *
 * Gratis, tanpa API key. Rate limit: 1 request/detik.
 * Docs: https://nominatim.org/release-docs/develop/api/Search/
 */
class GeocodingService
{
    /**
     * Nominatim API endpoint.
     */
    private const API_URL = 'https://nominatim.openstreetmap.org/search';

    /**
     * Cache TTL in seconds (30 hari — koordinat jarang berubah).
     */
    private const CACHE_TTL = 60 * 60 * 24 * 30;

    /**
     * Rate limit: minimum delay between requests (ms).
     */
    private const RATE_LIMIT_MS = 1100;

    /**
     * Geocode an address string into [latitude, longitude].
     *
     * @param  string  $address  Full address to geocode
     * @return array|null  ['latitude' => float, 'longitude' => float] or null
     */
    public function geocode(string $address): ?array
    {
        $address = trim($address);
        if (empty($address)) {
            return null;
        }

        // Check cache first
        $cacheKey = 'geocode:' . md5(strtolower($address));
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached ?: null; // false means "not found" cached
        }

        // Rate limit: wait if needed
        $this->respectRateLimit();

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'StudyTracerApp/1.0 (study-tracer geocoding)',
                'Accept-Language' => 'id,en',
            ])
            ->timeout(10)
            ->get(self::API_URL, [
                'q' => $address,
                'format' => 'jsonv2',
                'limit' => 1,
                'countrycodes' => 'id', // Bias ke Indonesia
                'addressdetails' => 0,
            ]);

            if ($response->successful()) {
                $results = $response->json();

                if (!empty($results) && isset($results[0]['lat'], $results[0]['lon'])) {
                    $result = [
                        'latitude' => (float) $results[0]['lat'],
                        'longitude' => (float) $results[0]['lon'],
                        'display_name' => $results[0]['display_name'] ?? null,
                    ];

                    // Cache the result
                    Cache::put($cacheKey, $result, self::CACHE_TTL);

                    Log::info("Geocoded: '{$address}' → [{$result['latitude']}, {$result['longitude']}]");
                    return $result;
                }
            }

            // Not found — cache as false to avoid re-querying
            Cache::put($cacheKey, false, self::CACHE_TTL);
            Log::warning("Geocode not found: '{$address}'");
            return null;

        } catch (\Exception $e) {
            Log::error("Geocoding error for '{$address}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Build a full address string for a Perusahaan.
     */
    public function buildPerusahaanAddress($perusahaan): string
    {
        $parts = [];

        if (!empty($perusahaan->nama_perusahaan)) {
            $parts[] = $perusahaan->nama_perusahaan;
        }
        if (!empty($perusahaan->jalan)) {
            $parts[] = $perusahaan->jalan;
        }
        if ($perusahaan->kota) {
            $parts[] = $perusahaan->kota->nama_kota;
            if ($perusahaan->kota->provinsi) {
                $parts[] = $perusahaan->kota->provinsi->nama_provinsi;
            }
        }
        $parts[] = 'Indonesia';

        return implode(', ', $parts);
    }

    /**
     * Build a full address string for a Universitas.
     */
    public function buildUniversitasAddress($universitas): string
    {
        $parts = [$universitas->nama_universitas];

        if (!empty($universitas->alamat)) {
            $parts[] = $universitas->alamat;
        }

        $parts[] = 'Indonesia';
        return implode(', ', $parts);
    }

    /**
     * Build a full address string for a Wirausaha.
     */
    public function buildWirausahaAddress($wirausaha): string
    {
        $parts = [$wirausaha->nama_usaha];

        if (!empty($wirausaha->alamat)) {
            $parts[] = $wirausaha->alamat;
        }

        $parts[] = 'Indonesia';
        return implode(', ', $parts);
    }

    /**
     * Build a full address string for a Kota.
     */
    public function buildKotaAddress($kota): string
    {
        $parts = [$kota->nama_kota];

        if ($kota->provinsi) {
            $parts[] = $kota->provinsi->nama_provinsi;
        }
        $parts[] = 'Indonesia';

        return implode(', ', $parts);
    }

    /**
     * Geocode a Perusahaan and update its coordinates.
     *
     * Strategi fallback (perusahaan jarang ada di OSM):
     *   1. Alamat jalan + kota (paling akurat)
     *   2. Nama perusahaan + kota
     *   3. Kota saja (fallback terakhir, level kota)
     *
     * @return bool true if coordinates were updated
     */
    public function geocodePerusahaan($perusahaan): bool
    {
        // Load relations if not loaded
        if (!$perusahaan->relationLoaded('kota')) {
            $perusahaan->load('kota.provinsi');
        }

        $result = null;

        // Attempt 1: Alamat jalan lengkap (tanpa nama perusahaan, biar fokus lokasi)
        if (!empty($perusahaan->jalan) && $perusahaan->kota) {
            $streetAddress = $perusahaan->jalan . ', ' . $perusahaan->kota->nama_kota;
            if ($perusahaan->kota->provinsi) {
                $streetAddress .= ', ' . $perusahaan->kota->provinsi->nama_provinsi;
            }
            $streetAddress .= ', Indonesia';
            $result = $this->geocode($streetAddress);
        }

        // Attempt 2: Nama perusahaan + kota
        if (!$result && $perusahaan->kota) {
            $result = $this->geocode(
                $perusahaan->nama_perusahaan . ', ' . $perusahaan->kota->nama_kota . ', Indonesia'
            );
        }

        // Attempt 3: Fallback ke koordinat kota
        if (!$result && $perusahaan->kota) {
            // Kalau kota sudah punya koordinat, pakai langsung
            if ($perusahaan->kota->latitude && $perusahaan->kota->longitude) {
                $result = [
                    'latitude' => $perusahaan->kota->latitude,
                    'longitude' => $perusahaan->kota->longitude,
                ];
            } else {
                $result = $this->geocode($perusahaan->kota->nama_kota . ', Indonesia');
            }
        }

        if ($result) {
            $perusahaan->update([
                'latitude' => $result['latitude'],
                'longitude' => $result['longitude'],
            ]);
            return true;
        }

        return false;
    }

    /**
     * Geocode a Universitas and update its coordinates.
     */
    public function geocodeUniversitas($universitas): bool
    {
        $address = $this->buildUniversitasAddress($universitas);
        $result = $this->geocode($address);

        if ($result) {
            $universitas->update([
                'latitude' => $result['latitude'],
                'longitude' => $result['longitude'],
            ]);
            return true;
        }

        return false;
    }

    /**
     * Geocode a Wirausaha and update its coordinates.
     */
    public function geocodeWirausaha($wirausaha): bool
    {
        $address = $this->buildWirausahaAddress($wirausaha);
        $result = $this->geocode($address);

        if ($result) {
            $wirausaha->update([
                'latitude' => $result['latitude'],
                'longitude' => $result['longitude'],
            ]);
            return true;
        }

        return false;
    }

    /**
     * Geocode a Kota and update its coordinates.
     */
    public function geocodeKota($kota): bool
    {
        if (!$kota->relationLoaded('provinsi')) {
            $kota->load('provinsi');
        }

        $address = $this->buildKotaAddress($kota);
        $result = $this->geocode($address);

        if ($result) {
            $kota->update([
                'latitude' => $result['latitude'],
                'longitude' => $result['longitude'],
            ]);
            return true;
        }

        return false;
    }

    /**
     * Enforce rate limit (1 request per second for Nominatim).
     */
    private function respectRateLimit(): void
    {
        $lastRequestKey = 'geocode:last_request_time';
        $lastTime = Cache::get($lastRequestKey, 0);
        $now = microtime(true) * 1000;

        $elapsed = $now - $lastTime;
        if ($elapsed < self::RATE_LIMIT_MS) {
            $waitMs = (int) (self::RATE_LIMIT_MS - $elapsed);
            usleep($waitMs * 1000);
        }

        Cache::put($lastRequestKey, microtime(true) * 1000, 60);
    }
}
