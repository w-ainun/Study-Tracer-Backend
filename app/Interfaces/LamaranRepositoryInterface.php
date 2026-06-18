<?php

namespace App\Interfaces;

interface LamaranRepositoryInterface
{
    /**
     * Alumni applies to a lowongan.
     */
    public function apply(int $alumniId, int $lowonganId, ?string $catatan = null);

    /**
     * Get lamaran history for a specific alumni (paginated).
     */
    public function getByAlumni(int $alumniId, array $filters = [], int $perPage = 15);

    /**
     * Update status of a lamaran (pending → diterima/ditolak).
     */
    public function updateStatus(int $lamaranId, string $status, ?string $catatanAdmin = null);

    /**
     * Get all lamaran for a specific lowongan (paginated).
     */
    public function getByLowongan(int $lowonganId, array $filters = [], int $perPage = 15);

    /**
     * Get lamaran statistics for an alumni.
     */
    public function getAlumniStats(int $alumniId): array;

    /**
     * Get global lamaran statistics (admin).
     */
    public function getGlobalStats(): array;

    /**
     * Get all lamaran with filters (admin, paginated).
     */
    public function getAll(array $filters = [], int $perPage = 15);

    /**
     * Find a lamaran by ID.
     */
    public function findById(int $lamaranId);

    /**
     * Delete (cancel) a pending lamaran.
     */
    public function delete(int $lamaranId): bool;
}
