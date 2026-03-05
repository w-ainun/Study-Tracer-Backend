<?php

namespace App\Services\Alumni;

use App\Interfaces\Alumni\AlumniDirectoryRepositoryInterface;

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
     * Get filter options for the directory dropdowns.
     */
    public function getFilterOptions(): array
    {
        return [
            'tahun'       => $this->directoryRepository->getTahunLulusOptions(),
            'status'      => $this->directoryRepository->getStatusOptions(),
            'universitas' => $this->directoryRepository->getUniversitasOptions(),
        ];
    }
}
