<?php

namespace App\Observers;

use App\Models\Universitas;
use App\Services\GeocodingService;

/**
 * Auto-geocode universitas saat dibuat/diupdate.
 */
class UniversitasObserver
{
    public function __construct(private GeocodingService $geocoding) {}

    public function created(Universitas $universitas): void
    {
        if (!$universitas->latitude || !$universitas->longitude) {
            dispatch(function () use ($universitas) {
                $this->geocoding->geocodeUniversitas($universitas->fresh());
            })->afterResponse();
        }
    }

    public function updated(Universitas $universitas): void
    {
        if ($universitas->wasChanged('nama_universitas')) {
            dispatch(function () use ($universitas) {
                $this->geocoding->geocodeUniversitas($universitas->fresh());
            })->afterResponse();
        }
    }
}
