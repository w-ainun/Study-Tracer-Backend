<?php

namespace App\Repositories;

use App\Interfaces\KemitraanRepositoryInterface;
use App\Models\Kemitraan;
use Illuminate\Database\Eloquent\Collection;

class KemitraanRepository implements KemitraanRepositoryInterface
{
    /**
     * Get all kemitraan by tipe with optional search.
     */
    public function getAll(string $tipe, ?string $search = null): Collection
    {
        $query = Kemitraan::query()
            ->where('tipe', $tipe)
            ->orderByDesc('id_kemitraan');

        if ($search) {
            $query->where('nama', 'like', "%{$search}%");
        }

        // Eager-load relation based on tipe
        if ($tipe === 'universitas') {
            $query->with('universitas');
        } else {
            $query->with('perusahaan');
        }

        return $query->get();
    }

    /**
     * Find a single kemitraan by ID with its related entity.
     */
    public function find(int $id): ?Kemitraan
    {
        return Kemitraan::with(['universitas', 'perusahaan'])->find($id);
    }

    /**
     * Create a new kemitraan record.
     */
    public function create(array $data): Kemitraan
    {
        return Kemitraan::create($data);
    }

    /**
     * Update an existing kemitraan record.
     */
    public function update(int $id, array $data): Kemitraan
    {
        $kemitraan = Kemitraan::findOrFail($id);
        $kemitraan->update($data);

        return $kemitraan->fresh();
    }

    /**
     * Delete a kemitraan record.
     */
    public function delete(int $id): bool
    {
        $kemitraan = Kemitraan::findOrFail($id);

        return $kemitraan->delete();
    }
}
