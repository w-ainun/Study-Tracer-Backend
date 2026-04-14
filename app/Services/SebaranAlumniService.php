<?php

namespace App\Services;

use App\Interfaces\SebaranAlumniRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class SebaranAlumniService
{
    private SebaranAlumniRepositoryInterface $repository;

    public function __construct(SebaranAlumniRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get all alumni map markers (cached 5 minutes).
     */
    public function getAlumniMapMarkers(array $filters = []): array
    {
        $cacheKey = 'sebaran.markers.' . md5(json_encode($filters));

        return Cache::remember($cacheKey, 300, function () use ($filters) {
            return $this->repository->getAlumniMapMarkers($filters);
        });
    }

    /**
     * Get alumni detail at a specific location.
     */
    public function getAlumniAtLocation(string $type, int $entityId, array $filters = []): array
    {
        $cacheKey = "sebaran.location.{$type}.{$entityId}." . md5(json_encode($filters));

        return Cache::remember($cacheKey, 300, function () use ($type, $entityId, $filters) {
            return $this->repository->getAlumniAtLocation($type, $entityId, $filters);
        });
    }

    /**
     * Get filter options (cached 15 minutes).
     */
    public function getFilterOptions(): array
    {
        return Cache::remember('sebaran.filter_options', 900, function () {
            return $this->repository->getFilterOptions();
        });
    }

    /**
     * Get sebaran statistics (cached 10 minutes).
     */
    public function getSebaranStats(array $filters = []): array
    {
        $cacheKey = 'sebaran.stats.' . md5(json_encode($filters));

        return Cache::remember($cacheKey, 600, function () use ($filters) {
            return $this->repository->getSebaranStats($filters);
        });
    }

    /**
     * Get heatmap data per provinsi (cached 10 minutes).
     */
    public function getHeatmapData(array $filters = []): array
    {
        $cacheKey = 'sebaran.heatmap.' . md5(json_encode($filters));

        return Cache::remember($cacheKey, 600, function () use ($filters) {
            return $this->repository->getHeatmapData($filters);
        });
    }

    /**
     * Search locations for autocomplete (not cached — real-time).
     */
    public function searchLocations(string $query, ?string $type = null): array
    {
        return $this->repository->searchLocations($query, $type);
    }
}
