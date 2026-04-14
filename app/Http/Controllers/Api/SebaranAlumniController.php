<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SebaranAlumniService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class SebaranAlumniController extends Controller
{
    use ApiResponse;

    private SebaranAlumniService $sebaranService;

    public function __construct(SebaranAlumniService $sebaranService)
    {
        $this->sebaranService = $sebaranService;
    }

    /**
     * GET /admin/sebaran/markers
     *
     * Get all alumni map markers for the interactive map.
     * Supports filtering by angkatan, perusahaan, universitas, tipe karir, etc.
     *
     * Query params:
     *   - tipe_karir: bekerja|kuliah|wirausaha
     *   - angkatan: tahun_masuk (e.g. 2020)
     *   - perusahaan_id: filter by perusahaan
     *   - universitas_id: filter by universitas
     *   - provinsi_id: filter by provinsi
     *   - kota_id: filter by kota
     *   - jurusan_id: filter by jurusan SMK
     *   - bidang_usaha_id: filter by bidang wirausaha
     */
    public function markers(Request $request)
    {
        try {
            $filters = $request->only([
                'tipe_karir',
                'angkatan',
                'perusahaan_id',
                'universitas_id',
                'provinsi_id',
                'kota_id',
                'jurusan_id',
                'bidang_usaha_id',
            ]);

            $data = $this->sebaranService->getAlumniMapMarkers($filters);

            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data marker sebaran alumni: ' . $e->getMessage());
        }
    }

    /**
     * GET /admin/sebaran/location/{type}/{id}
     *
     * Get all alumni at a specific location for detail popup.
     * When a marker is clicked, this returns the full alumni list with photos
     * so they can be displayed as circular profile icons on the map.
     *
     * @param string $type  bekerja|kuliah|wirausaha|perusahaan|universitas
     * @param int    $id    Entity ID
     */
    public function alumniAtLocation(Request $request, string $type, int $id)
    {
        try {
            $filters = $request->only(['angkatan']);

            $data = $this->sebaranService->getAlumniAtLocation($type, $id, $filters);

            if (!$data['entity']) {
                return $this->notFoundResponse('Lokasi tidak ditemukan');
            }

            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil detail alumni di lokasi: ' . $e->getMessage());
        }
    }

    /**
     * GET /admin/sebaran/filters
     *
     * Get all available filter options for the map interface.
     * Returns lists of angkatan, perusahaan, universitas, bidang usaha,
     * provinsi, jurusan, and tipe karir.
     */
    public function filters()
    {
        try {
            $data = $this->sebaranService->getFilterOptions();

            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil opsi filter sebaran: ' . $e->getMessage());
        }
    }

    /**
     * GET /admin/sebaran/stats
     *
     * Get sebaran statistics summary.
     * Shows total mapped alumni, breakdown by career type,
     * top companies, top universities, and percentage distributions.
     */
    public function stats(Request $request)
    {
        try {
            $filters = $request->only(['angkatan', 'jurusan_id']);

            $data = $this->sebaranService->getSebaranStats($filters);

            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil statistik sebaran: ' . $e->getMessage());
        }
    }

    /**
     * GET /admin/sebaran/heatmap
     *
     * Get heatmap data per provinsi for choropleth/heat visualization.
     * Returns alumni count and percentage per province.
     */
    public function heatmap(Request $request)
    {
        try {
            $filters = $request->only(['angkatan', 'jurusan_id']);

            $data = $this->sebaranService->getHeatmapData($filters);

            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data heatmap: ' . $e->getMessage());
        }
    }

    /**
     * GET /admin/sebaran/search
     *
     * Search locations for autocomplete.
     * Searches perusahaan, universitas, and kota names.
     *
     * Query params:
     *   - q: search query (required, min 2 chars)
     *   - type: perusahaan|universitas|kota (optional, search all if omitted)
     */
    public function search(Request $request)
    {
        try {
            $query = $request->input('q', '');

            if (strlen($query) < 2) {
                return $this->successResponse([]);
            }

            $type = $request->input('type');
            $data = $this->sebaranService->searchLocations($query, $type);

            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mencari lokasi: ' . $e->getMessage());
        }
    }
}
