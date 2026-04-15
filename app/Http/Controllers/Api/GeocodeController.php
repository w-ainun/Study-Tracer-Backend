<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GeocodingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API endpoints for the frontend map picker (Shopee-style).
 *
 * - Reverse geocode: lat/lng → address text
 * - Forward search: text → list of locations
 */
class GeocodeController extends Controller
{
    public function __construct(private GeocodingService $geocoding) {}

    /**
     * GET /api/geocode/reverse?lat=X&lng=Y
     *
     * Convert coordinates to address. Used when user drops a pin on the map.
     */
    public function reverse(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $result = $this->geocoding->reverseGeocode(
            (float) $request->lat,
            (float) $request->lng
        );

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Alamat tidak ditemukan untuk koordinat tersebut.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * GET /api/geocode/search?q=alamat&limit=5
     *
     * Search for addresses. Used by the map picker search box.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $results = $this->geocoding->searchAddress(
            $request->q,
            $request->integer('limit', 5)
        );

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }
}
