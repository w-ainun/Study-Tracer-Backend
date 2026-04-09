<?php

namespace App\Interfaces;

use App\Models\PengaturanTampilan;
use App\Models\PengaturanTampilanHistory;

interface PengaturanTampilanRepositoryInterface
{
    /**
     * Get the current display settings (singleton row).
     */
    public function get(): PengaturanTampilan;

    /**
     * Update the display settings.
     */
    public function update(array $data): PengaturanTampilan;

    /**
     * Save a snapshot of current settings to history before an update.
     */
    public function saveHistory(PengaturanTampilan $settings, int $changedBy, string $changeType = 'update'): PengaturanTampilanHistory;

    /**
     * Get the most recent history snapshot (for revert).
     */
    public function getLatestHistory(): ?PengaturanTampilanHistory;

    /**
     * Reset all settings to factory defaults.
     */
    public function resetToDefaults(): PengaturanTampilan;

    /**
     * Delete a specific history record.
     */
    public function deleteHistory(int $historyId): void;
}

