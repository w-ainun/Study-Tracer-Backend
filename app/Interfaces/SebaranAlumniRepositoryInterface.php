<?php

namespace App\Interfaces;

interface SebaranAlumniRepositoryInterface
{
    /**
     * Get all alumni map markers clustered by location entity.
     * Each marker represents a perusahaan/universitas/wirausaha location.
     */
    public function getAlumniMapMarkers(array $filters = []): array;

    /**
     * Get alumni detail at a specific location (for popup on marker click).
     *
     * @param string $type  bekerja|kuliah|wirausaha
     * @param int    $entityId  id_perusahaan|id_universitas|id_wirausaha
     */
    public function getAlumniAtLocation(string $type, int $entityId, array $filters = []): array;

    /**
     * Get available filter options (angkatan, perusahaan, universitas, etc).
     */
    public function getFilterOptions(): array;

    /**
     * Get sebaran statistics (counts per type, top entities, per-provinsi).
     */
    public function getSebaranStats(array $filters = []): array;

    /**
     * Get heatmap data grouped by provinsi.
     */
    public function getHeatmapData(array $filters = []): array;

    /**
     * Search locations for autocomplete.
     */
    public function searchLocations(string $query, ?string $type = null): array;
}
