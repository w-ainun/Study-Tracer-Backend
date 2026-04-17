<?php

namespace App\Observers;

use App\Models\Perusahaan;
use App\Services\GeocodingService;

/**
 * Auto-geocode perusahaan saat dibuat/diupdate.
 *
 * SKIP geocoding jika user sudah set koordinat manual via map picker.
 */
class PerusahaanObserver
{
    public function __construct(private GeocodingService $geocoding) {}

    /**
     * Handle the Perusahaan "created" event.
     */
    public function created(Perusahaan $perusahaan): void
    {
        // Skip jika user sudah set koordinat manual (via map picker)
        if ($perusahaan->latitude && $perusahaan->longitude) {
            return;
        }

        dispatch(function () use ($perusahaan) {
            $this->geocoding->geocodePerusahaan($perusahaan->fresh());
        })->afterResponse();
    }

    /**
     * Handle the Perusahaan "updated" event.
     *
     * Re-geocode HANYA jika alamat berubah DAN user TIDAK set koordinat manual.
     */
    public function updated(Perusahaan $perusahaan): void
    {
        // Jika user baru saja set lat/lng manual (via map picker), jangan re-geocode
        if ($perusahaan->wasChanged(['latitude', 'longitude'])) {
            return;
        }

        $addressChanged = $perusahaan->wasChanged(['nama_perusahaan', 'jalan', 'id_kota']);

        if ($addressChanged) {
            dispatch(function () use ($perusahaan) {
                $fresh = $perusahaan->fresh();
                // Double-check: jika sudah punya koordinat yang valid, skip
                if ($fresh->latitude && $fresh->longitude) {
                    return;
                }
                $this->geocoding->geocodePerusahaan($fresh);
            })->afterResponse();
        }
    }
}
