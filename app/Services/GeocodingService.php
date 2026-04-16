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
        // v2 key to invalidate older low-accuracy cache entries
        $cacheKey = 'geocode:v2:' . md5(strtolower($address));
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached ?: null; // false means "not found" cached
        }

        // Rate limit: wait if needed
        $this->respectRateLimit();

        try {
            $response = Http::withoutVerifying()->withHeaders([
                'User-Agent' => 'StudyTracerApp/1.0 (study-tracer geocoding)',
                'Accept-Language' => 'id,en',
            ])
            ->timeout(10)
            ->get(self::API_URL, [
                'q' => $address,
                'format' => 'jsonv2',
                'limit' => 3,
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
            $normalized = $this->normalizeAddressSegment($perusahaan->jalan);
            if ($normalized) {
                $parts[] = $normalized;
            }
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
            $normalized = $this->normalizeAddressSegment($universitas->alamat);
            if ($normalized) {
                $parts[] = $normalized;
            }
        }
        if ($universitas->kota) {
            $parts[] = $universitas->kota->nama_kota;
            if ($universitas->kota->provinsi) {
                $parts[] = $universitas->kota->provinsi->nama_provinsi;
            }
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
            $normalized = $this->normalizeAddressSegment($wirausaha->alamat);
            if ($normalized) {
                $parts[] = $normalized;
            }
        }
        if ($wirausaha->kota) {
            $parts[] = $wirausaha->kota->nama_kota;
            if ($wirausaha->kota->provinsi) {
                $parts[] = $wirausaha->kota->provinsi->nama_provinsi;
            }
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
        $hasDetailedAddress = $this->hasMeaningfulAddress($perusahaan->jalan ?? null);

        // Attempt 1: Alamat jalan lengkap (paling akurat)
        if (!empty($perusahaan->jalan) && $perusahaan->kota) {
            $streetAddress = $this->buildContextAddress(
                $perusahaan->jalan,
                $perusahaan->kota->nama_kota ?? null,
                $perusahaan->kota->provinsi->nama_provinsi ?? null
            );

            if ($streetAddress) {
                $result = $this->geocode($streetAddress);
            }
        }

        // Attempt 2: Nama + alamat + kota
        if (!$result && $hasDetailedAddress && $perusahaan->kota) {
            $nameAndStreet = $perusahaan->nama_perusahaan . ', ' . $perusahaan->jalan . ', ' . $perusahaan->kota->nama_kota;
            if ($perusahaan->kota->provinsi) {
                $nameAndStreet .= ', ' . $perusahaan->kota->provinsi->nama_provinsi;
            }
            $nameAndStreet .= ', Indonesia';
            $result = $this->geocode($nameAndStreet);
        }

        // Attempt 3: Nama perusahaan + kota
        if (!$result && $perusahaan->kota) {
            $result = $this->geocode(
                $perusahaan->nama_perusahaan . ', ' . $perusahaan->kota->nama_kota . ', Indonesia'
            );
        }

        // Attempt 4: Fallback ke koordinat kota jika tidak ditemukan sama sekali
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
     *
     * Strategi: Universitas biasanya POI terkenal di OpenStreetMap,
     * jadi cari by nama dulu (lebih akurat), baru fallback ke alamat.
     */
    public function geocodeUniversitas($universitas): bool
    {
        if (!$universitas->relationLoaded('kota')) {
            $universitas->load('kota.provinsi');
        }

        $result = null;
        $nama = $universitas->nama_universitas;

        // Attempt 1: Nama universitas + kota (paling akurat untuk POI)
        if ($universitas->kota) {
            $query = $nama . ', ' . $universitas->kota->nama_kota;
            if ($universitas->kota->provinsi) {
                $query .= ', ' . $universitas->kota->provinsi->nama_provinsi;
            }
            $query .= ', Indonesia';
            $result = $this->geocode($query);
        }

        // Attempt 2: Nama universitas + Indonesia saja
        if (!$result) {
            $result = $this->geocode($nama . ', Indonesia');
        }

        // Attempt 3: Alamat detail (jika ada) + kota
        if (!$result && $this->hasMeaningfulAddress($universitas->alamat ?? null) && $universitas->kota) {
            $addressQuery = $this->buildContextAddress(
                $universitas->alamat,
                $universitas->kota->nama_kota ?? null,
                $universitas->kota->provinsi->nama_provinsi ?? null
            );
            if ($addressQuery) {
                $result = $this->geocode($addressQuery);
            }
        }

        // Attempt 4: Fallback ke koordinat kota
        if (!$result && $universitas->kota) {
            if ($universitas->kota->latitude && $universitas->kota->longitude) {
                $result = [
                    'latitude' => $universitas->kota->latitude,
                    'longitude' => $universitas->kota->longitude,
                ];
            }
        }

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
        if (!$wirausaha->relationLoaded('kota')) {
            $wirausaha->load('kota.provinsi');
        }

        $result = null;
        $hasDetailedAddress = $this->hasMeaningfulAddress($wirausaha->alamat ?? null);

        // Attempt 1: alamat + kota + provinsi
        if ($hasDetailedAddress && $wirausaha->kota) {
            $addressOnly = $this->buildContextAddress(
                $wirausaha->alamat,
                $wirausaha->kota->nama_kota ?? null,
                $wirausaha->kota->provinsi->nama_provinsi ?? null
            );

            if ($addressOnly) {
                $result = $this->geocode($addressOnly);
            }
        }

        // Attempt 2: nama usaha + alamat + kota + provinsi
        if (!$result) {
            $address = $this->buildWirausahaAddress($wirausaha);
            $result = $this->geocode($address);
        }

        // Fallback: koordinat kota jika address detail tidak ditemukan
        if (!$result && $wirausaha->kota) {
            if ($wirausaha->kota->latitude && $wirausaha->kota->longitude) {
                $result = [
                    'latitude' => $wirausaha->kota->latitude,
                    'longitude' => $wirausaha->kota->longitude,
                ];
            } else {
                $result = $this->geocode($wirausaha->kota->nama_kota . ', Indonesia');
            }
        }

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

    /**
     * Detect whether an address contains meaningful detail beyond placeholder values.
     */
    private function hasMeaningfulAddress(?string $address): bool
    {
        if (!$address) {
            return false;
        }

        $normalized = trim(mb_strtolower($address));
        if ($normalized === '' || $normalized === '-' || $normalized === 'n/a') {
            return false;
        }

        return true;
    }

    /**
     * Simplify noisy Indonesian address strings to improve geocoding hit-rate.
     */
    private function normalizeAddressSegment(?string $address): ?string
    {
        if (!$this->hasMeaningfulAddress($address)) {
            return null;
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', (string) $address))));

        $filtered = [];
        foreach ($parts as $part) {
            // Drop very broad administrative hints (will be appended from city/province context)
            if (preg_match('/^(kec\.?|kecamatan|kel\.?|kelurahan|kota|kabupaten|provinsi)\b/i', $part)) {
                continue;
            }

            // Remove postal codes to reduce ambiguity/noise
            $part = preg_replace('/\b\d{5}\b/', '', $part);
            $part = trim((string) $part);

            if ($part !== '') {
                $filtered[] = $part;
            }
        }

        if (empty($filtered)) {
            return null;
        }

        // Keep at most first 2 chunks (street + local area)
        $filtered = array_slice($filtered, 0, 2);

        return implode(', ', $filtered);
    }

    /**
     * Build address query and avoid duplicate city/province text.
     */
    private function buildContextAddress(?string $address, ?string $city, ?string $province): ?string
    {
        $normalizedAddress = $this->normalizeAddressSegment($address);
        $parts = [];

        if ($normalizedAddress) {
            $parts[] = $normalizedAddress;
        }

        if (!empty($city)) {
            $parts[] = $city;
        }

        if (!empty($province)) {
            $parts[] = $province;
        }

        $parts[] = 'Indonesia';

        // Deduplicate while preserving order
        $unique = [];
        foreach ($parts as $part) {
            $key = mb_strtolower(trim((string) $part));
            if ($key === '' || isset($unique[$key])) {
                continue;
            }
            $unique[$key] = trim((string) $part);
        }

        if (count($unique) <= 1) {
            return null;
        }

        return implode(', ', array_values($unique));
    }

    // ──────────────────────────────────────────────────
    // Map Picker API helpers
    // ──────────────────────────────────────────────────

    /**
     * Reverse geocode: convert lat/lng → address string.
     * Used by the frontend map picker to show address when user drops a pin.
     *
     * @return array|null ['display_name' => string, 'address' => array]
     */
    public function reverseGeocode(float $lat, float $lng): ?array
    {
        $cacheKey = 'geocode:reverse:' . md5("{$lat},{$lng}");
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached ?: null;
        }

        $this->respectRateLimit();

        try {
            $response = Http::withoutVerifying()->withHeaders([
                'User-Agent' => 'StudyTracerApp/1.0 (study-tracer geocoding)',
                'Accept-Language' => 'id,en',
            ])
            ->timeout(10)
            ->get('https://nominatim.openstreetmap.org/reverse', [
                'lat' => $lat,
                'lon' => $lng,
                'format' => 'jsonv2',
                'addressdetails' => 1,
                'zoom' => 18,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (!empty($data['display_name'])) {
                    $result = [
                        'display_name' => $data['display_name'],
                        'address' => $data['address'] ?? [],
                        'latitude' => (float) $data['lat'],
                        'longitude' => (float) $data['lon'],
                    ];

                    Cache::put($cacheKey, $result, self::CACHE_TTL);
                    return $result;
                }
            }

            Cache::put($cacheKey, false, self::CACHE_TTL);
            return null;

        } catch (\Exception $e) {
            Log::error("Reverse geocode error for [{$lat}, {$lng}]: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Forward geocode search: return multiple results for a text query.
     * Used by the frontend map picker search box.
     *
     * @return array of ['latitude', 'longitude', 'display_name']
     */
    public function searchAddress(string $query, int $limit = 5): array
    {
        $query = trim($query);
        if (empty($query)) {
            return [];
        }

        $this->respectRateLimit();

        try {
            $response = Http::withoutVerifying()->withHeaders([
                'User-Agent' => 'StudyTracerApp/1.0 (study-tracer geocoding)',
                'Accept-Language' => 'id,en',
            ])
            ->timeout(10)
            ->get(self::API_URL, [
                'q' => $query,
                'format' => 'jsonv2',
                'limit' => min($limit, 10),
                'countrycodes' => 'id',
                'addressdetails' => 1,
            ]);

            if ($response->successful()) {
                $results = $response->json();

                return collect($results)->map(fn($r) => [
                    'latitude' => (float) $r['lat'],
                    'longitude' => (float) $r['lon'],
                    'display_name' => $r['display_name'] ?? '',
                    'address' => $r['address'] ?? [],
                ])->toArray();
            }

            return [];

        } catch (\Exception $e) {
            Log::error("Address search error for '{$query}': " . $e->getMessage());
            return [];
        }
    }
}
