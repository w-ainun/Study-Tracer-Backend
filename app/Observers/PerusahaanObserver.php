<?php

namespace App\Observers;

use App\Models\Perusahaan;
use App\Services\GeocodingService;

/**
 * Auto-geocode perusahaan saat dibuat/diupdate.
 *
 * Hanya geocode jika belum punya koordinat, atau jika alamat berubah.
 */
class PerusahaanObserver
{
    public function __construct(private GeocodingService $geocoding) {}

    /**
     * Handle the Perusahaan "created" event.
     */
    public function created(Perusahaan $perusahaan): void
    {
        if (!$perusahaan->latitude || !$perusahaan->longitude) {
            // Dispatch ke queue agar tidak blocking request
            dispatch(function () use ($perusahaan) {
                $this->geocoding->geocodePerusahaan($perusahaan->fresh());
            })->afterResponse();
        }
    }

    /**
     * Handle the Perusahaan "updated" event.
     *
     * Re-geocode hanya jika nama/alamat/kota berubah.
     */
    public function updated(Perusahaan $perusahaan): void
    {
        $addressChanged = $perusahaan->wasChanged(['nama_perusahaan', 'jalan', 'id_kota']);

        if ($addressChanged) {
            dispatch(function () use ($perusahaan) {
                $this->geocoding->geocodePerusahaan($perusahaan->fresh());
            })->afterResponse();
        }
    }
}
