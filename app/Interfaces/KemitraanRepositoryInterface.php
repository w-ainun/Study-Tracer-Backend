<?php

namespace App\Interfaces;

use App\Models\Universitas;
use App\Models\Perusahaan;
use Illuminate\Database\Eloquent\Collection;

interface KemitraanRepositoryInterface
{
    // ── Mitra Universitas ────────────────────────────────────

    /**
     * Get all universitas, optionally filtered by search term.
     */
    public function getAllUniversitas(?string $search = null): Collection;

    /**
     * Find a single universitas by ID.
     */
    public function findUniversitas(int $id): ?Universitas;

    /**
     * Create a new universitas record.
     */
    public function createUniversitas(array $data): Universitas;

    /**
     * Update an existing universitas record.
     */
    public function updateUniversitas(int $id, array $data): Universitas;

    /**
     * Delete a universitas record.
     */
    public function deleteUniversitas(int $id): bool;

    // ── Mitra Perusahaan ─────────────────────────────────────

    /**
     * Get all perusahaan, optionally filtered by search term.
     */
    public function getAllPerusahaan(?string $search = null): Collection;

    /**
     * Find a single perusahaan by ID.
     */
    public function findPerusahaan(int $id): ?Perusahaan;

    /**
     * Create a new perusahaan record.
     */
    public function createPerusahaan(array $data): Perusahaan;

    /**
     * Update an existing perusahaan record.
     */
    public function updatePerusahaan(int $id, array $data): Perusahaan;

    /**
     * Delete a perusahaan record.
     */
    public function deletePerusahaan(int $id): bool;
}
