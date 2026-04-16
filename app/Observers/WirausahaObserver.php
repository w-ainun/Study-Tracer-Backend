<?php

namespace App\Observers;

use App\Models\Wirausaha;
use App\Services\GeocodingService;

/**
 * Auto-geocode wirausaha saat dibuat/diupdate.
 *
 * SKIP geocoding jika user sudah set koordinat manual via map picker.
 */
class WirausahaObserver
{
    public function __construct(private GeocodingService $geocoding) {}

    public function created(Wirausaha $wirausaha): void
    {
        // Skip jika user sudah set koordinat manual (via map picker)
        if ($wirausaha->latitude && $wirausaha->longitude) {
            return;
        }

        dispatch(function () use ($wirausaha) {
            $this->geocoding->geocodeWirausaha($wirausaha->fresh());
        })->afterResponse();
    }

    public function updated(Wirausaha $wirausaha): void
    {
        // Jika user baru saja set lat/lng manual, jangan re-geocode
        if ($wirausaha->wasChanged(['latitude', 'longitude'])) {
            return;
        }

        if ($wirausaha->wasChanged(['nama_usaha', 'alamat'])) {
            dispatch(function () use ($wirausaha) {
                $fresh = $wirausaha->fresh();
                if ($fresh->latitude && $fresh->longitude) {
                    return;
                }
                $this->geocoding->geocodeWirausaha($fresh);
            })->afterResponse();
        }
    }
}
