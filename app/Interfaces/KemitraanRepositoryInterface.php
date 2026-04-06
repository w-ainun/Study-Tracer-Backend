<?php

namespace App\Interfaces;

use App\Models\Kemitraan;
use Illuminate\Database\Eloquent\Collection;

interface KemitraanRepositoryInterface
{
    /**
     * Get all kemitraan by tipe, optionally filtered by search term.
     */
    public function getAll(string $tipe, ?string $search = null): Collection;

    /**
     * Find a single kemitraan by ID.
     */
    public function find(int $id): ?Kemitraan;

    /**
     * Create a new kemitraan record.
     */
    public function create(array $data): Kemitraan;

    /**
     * Update an existing kemitraan record.
     */
    public function update(int $id, array $data): Kemitraan;

    /**
     * Delete a kemitraan record.
     */
    public function delete(int $id): bool;
}
