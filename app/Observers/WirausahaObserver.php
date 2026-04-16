<?php

namespace App\Observers;

use App\Models\Wirausaha;
use App\Services\GeocodingService;

/**
 * Auto-geocode wirausaha saat dibuat/diupdate.
 */
class WirausahaObserver
{
    public function __construct(private GeocodingService $geocoding) {}

    public function created(Wirausaha $wirausaha): void
    {
        if (!$wirausaha->latitude || !$wirausaha->longitude) {
            dispatch(function () use ($wirausaha) {
                $this->geocoding->geocodeWirausaha($wirausaha->fresh());
            })->afterResponse();
        }
    }

    public function updated(Wirausaha $wirausaha): void
    {
        if ($wirausaha->wasChanged(['nama_usaha', 'alamat'])) {
            dispatch(function () use ($wirausaha) {
                $this->geocoding->geocodeWirausaha($wirausaha->fresh());
            })->afterResponse();
        }
    }
}
