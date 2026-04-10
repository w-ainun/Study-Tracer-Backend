<?php

namespace App\Repositories;

use App\Interfaces\PengaturanTampilanRepositoryInterface;
use App\Models\PengaturanTampilan;
use App\Models\PengaturanTampilanHistory;
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
            $settings = PengaturanTampilan::find(1);

            if (!$settings) {
                $settings = new PengaturanTampilan();
                $settings->id             = 1;
                $settings->nama_sekolah   = 'SMK Negeri 1 Gondang';
                $settings->primary_color  = '#3C5759';
                $settings->secondary_color = '#F3F4F4';
                $settings->third_color    = '#9CA3AF';
                $settings->save();
            }

            return $settings;
        });
    }

    /**
     * Update the display settings and bust cache.
     */
    public function update(array $data): PengaturanTampilan
    {
        // Cari record dengan find() untuk menghindari mass assignment pada 'id'
        $settings = PengaturanTampilan::find(1);

        if (!$settings) {
            // Buat record baru tanpa mass assignment (set id langsung)
            $settings = new PengaturanTampilan();
            $settings->id             = 1;
            $settings->nama_sekolah   = 'SMK Negeri 1 Gondang';
            $settings->primary_color  = '#3C5759';
            $settings->secondary_color = '#F3F4F4';
            $settings->third_color    = '#9CA3AF';
            $settings->save();
        }

        // Pastikan 'id' tidak ikut di-mass-assign
        unset($data['id']);

        $settings->update($data);

        Cache::forget(self::CACHE_KEY);

        return $settings->fresh();
    }

    /**
     * Save a snapshot of current settings to history before an update.
     */
    public function saveHistory(PengaturanTampilan $settings, int $changedBy, string $changeType = 'update'): PengaturanTampilanHistory
    {
        return PengaturanTampilanHistory::create([
            'pengaturan_tampilan_id' => $settings->id,
            'snapshot'               => $settings->toSnapshot(),
            'changed_by'             => $changedBy,
            'change_type'            => $changeType,
            'created_at'             => now(),
        ]);
    }

    /**
     * Get the most recent history snapshot (for revert).
     */
    public function getLatestHistory(): ?PengaturanTampilanHistory
    {
        return PengaturanTampilanHistory::where('pengaturan_tampilan_id', 1)
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Reset all settings to factory defaults and bust cache.
     */
    public function resetToDefaults(): PengaturanTampilan
    {
        $settings = PengaturanTampilan::firstOrCreate(
            ['id' => 1],
            PengaturanTampilan::FACTORY_DEFAULTS
        );

        $settings->update(PengaturanTampilan::FACTORY_DEFAULTS);

        Cache::forget(self::CACHE_KEY);

        return $settings->fresh();
    }

    /**
     * Delete a specific history record.
     */
    public function deleteHistory(int $historyId): void
    {
        PengaturanTampilanHistory::where('id', $historyId)->delete();
    }
}

