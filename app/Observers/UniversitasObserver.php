<?php

namespace App\Observers;

use App\Models\Universitas;
use App\Services\GeocodingService;

/**
 * Auto-geocode universitas saat dibuat/diupdate.
 *
 * SKIP geocoding jika user sudah set koordinat manual via map picker.
 */
class UniversitasObserver
{
    public function __construct(private GeocodingService $geocoding) {}

    public function created(Universitas $universitas): void
    {
        // Skip jika user sudah set koordinat manual (via map picker)
        if ($universitas->latitude && $universitas->longitude) {
            return;
        }

        dispatch(function () use ($universitas) {
            $this->geocoding->geocodeUniversitas($universitas->fresh());
        })->afterResponse();
    }

    public function updated(Universitas $universitas): void
    {
        // Jika user baru saja set lat/lng manual, jangan re-geocode
        if ($universitas->wasChanged(['latitude', 'longitude'])) {
            return;
        }

        if ($universitas->wasChanged('nama_universitas')) {
            dispatch(function () use ($universitas) {
                $fresh = $universitas->fresh();
                if ($fresh->latitude && $fresh->longitude) {
                    return;
                }
                $this->geocoding->geocodeUniversitas($fresh);
            })->afterResponse();
        }
    }
}
