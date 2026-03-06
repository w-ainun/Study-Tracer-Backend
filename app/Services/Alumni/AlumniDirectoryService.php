<?php

namespace App\Services\Alumni;

use App\Interfaces\Alumni\AlumniDirectoryRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class AlumniDirectoryService
{
    private AlumniDirectoryRepositoryInterface $directoryRepository;

    public function __construct(AlumniDirectoryRepositoryInterface $directoryRepository)
    {
        $this->directoryRepository = $directoryRepository;
    }

    /**
     * Get paginated alumni directory with search + filter.
     */
    public function getAlumniDirectory(array $filters = [], int $perPage = 12)
    {
        return $this->directoryRepository->getVerifiedAlumni($filters, $perPage);
    }

    /**
     * Get a single alumni's public profile (no sensitive data).
     */
    public function getAlumniPublicProfile(int $alumniId)
    {
        return $this->directoryRepository->getAlumniPublicProfile($alumniId);
    }

    /**
     * Get filter options for the directory dropdowns (cached 10 minutes).
     */
    public function getFilterOptions(): array
    {
        return Cache::remember('directory.filter_options', 600, function () {
            return [
                'tahun'       => $this->directoryRepository->getTahunLulusOptions(),
                'status'      => $this->directoryRepository->getStatusOptions(),
                'universitas' => $this->directoryRepository->getUniversitasOptions(),
            ];
        });
    }
}
