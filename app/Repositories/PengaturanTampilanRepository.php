<?php

namespace App\Repositories;

use App\Interfaces\PengaturanTampilanRepositoryInterface;
use App\Models\PengaturanTampilan;
use Illuminate\Support\Facades\Cache;

class PengaturanTampilanRepository implements PengaturanTampilanRepositoryInterface
{
    private const CACHE_KEY = 'pengaturan_tampilan';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get the current display settings.
     * Uses firstOrCreate to ensure a row always exists (singleton pattern).
     * Cached for 1 hour to avoid repeated DB queries.
     */
    public function get(): PengaturanTampilan
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return PengaturanTampilan::firstOrCreate(
                ['id' => 1],
                [
                    'nama_sekolah'    => 'SMK Negeri 1 Gondang',
                    'primary_color'   => '#3C5759',
                    'secondary_color' => '#F3F4F4',
                    'third_color'     => '#9CA3AF',
                ]
            );
        });
    }

    /**
     * Update the display settings and bust cache.
     */
    public function update(array $data): PengaturanTampilan
    {
        $settings = PengaturanTampilan::firstOrCreate(
            ['id' => 1],
            [
                'nama_sekolah'    => 'SMK Negeri 1 Gondang',
                'primary_color'   => '#3C5759',
                'secondary_color' => '#F3F4F4',
                'third_color'     => '#9CA3AF',
            ]
        );

        $settings->update($data);

        Cache::forget(self::CACHE_KEY);

        return $settings->fresh();
    }
}
