<?php

namespace App\Interfaces\Alumni;

interface AlumniDirectoryRepositoryInterface
{
    /**
     * Get paginated verified alumni with filters.
     *
     * @param array $filters  [search, tahun, status, universitas]
     * @param int   $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getVerifiedAlumni(array $filters = [], int $perPage = 12);

    /**
     * Get distinct graduation years from verified alumni for filter options.
     *
     * @return array
     */
    public function getTahunLulusOptions(): array;

    /**
     * Get distinct status names from verified alumni for filter options.
     *
     * @return array
     */
    public function getStatusOptions(): array;

    /**
     * Get distinct universitas names from verified alumni who are studying.
     *
     * @return array
     */
    public function getUniversitasOptions(): array;
}
