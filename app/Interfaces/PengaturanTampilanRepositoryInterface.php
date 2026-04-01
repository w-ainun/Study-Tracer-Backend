<?php

namespace App\Interfaces;

use App\Models\PengaturanTampilan;

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
}
