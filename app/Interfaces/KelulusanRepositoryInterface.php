<?php

namespace App\Interfaces;

interface KelulusanRepositoryInterface
{
    // ── Calon Lulusan (Staging) ──────────────────

    /**
     * Get all calon lulusan with filters and pagination.
     */
    public function getCalonLulusan(array $filters = [], int $perPage = 50);

    /**
     * Create a single calon lulusan entry.
     */
    public function createCalonLulusan(array $data);

    /**
     * Bulk insert calon lulusan from Excel import.
     * Returns the count of inserted rows.
     */
    public function bulkCreateCalonLulusan(array $rows): int;

    /**
     * Delete a single calon lulusan by ID.
     */
    public function deleteCalonLulusan(int $id): bool;

    /**
     * Delete all calon lulusan (clear staging table).
     */
    public function clearCalonLulusan(): int;

    /**
     * Get total count of calon lulusan (with optional filters).
     */
    public function countCalonLulusan(array $filters = []): int;

    // ── Riwayat Kelulusan (Confirmed) ────────────

    /**
     * Get all riwayat kelulusan with filters and pagination.
     */
    public function getRiwayatKelulusan(array $filters = [], int $perPage = 15);

    /**
     * Bulk insert riwayat kelulusan from confirmed calon lulusan.
     * Returns the count of inserted rows.
     */
    public function bulkCreateRiwayatKelulusan(array $rows): int;

    /**
     * Get distinct tahun_lulus values for filter dropdown.
     */
    public function getDistinctTahunLulus(): array;

    /**
     * Get statistics for kelulusan dashboard.
     */
    public function getStats(): array;

    /**
     * Stream riwayat kelulusan for CSV/Excel export.
     */
    public function streamRiwayatKelulusan(array $filters = [], callable $callback);
}
