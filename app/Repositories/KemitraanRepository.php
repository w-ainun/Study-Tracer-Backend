<?php

namespace App\Repositories;

use App\Interfaces\KemitraanRepositoryInterface;
use App\Models\Universitas;
use App\Models\Perusahaan;
use Illuminate\Database\Eloquent\Collection;

class KemitraanRepository implements KemitraanRepositoryInterface
{
    // ═══════════════════════════════════════════════════════════
    //  MITRA UNIVERSITAS
    // ═══════════════════════════════════════════════════════════

    /**
     * Get all universitas, optionally filtered by search term.
     */
    public function getAllUniversitas(?string $search = null): Collection
    {
        $query = Universitas::query()->orderByDesc('id_universitas');

        if ($search) {
            $query->where('nama_universitas', 'like', "%{$search}%");
        }

        return $query->get();
    }

    /**
     * Find a single universitas by ID.
     */
    public function findUniversitas(int $id): ?Universitas
    {
        return Universitas::find($id);
    }

    /**
     * Create a new universitas record.
     */
    public function createUniversitas(array $data): Universitas
    {
        return Universitas::create($data);
    }

    /**
     * Update an existing universitas record.
     */
    public function updateUniversitas(int $id, array $data): Universitas
    {
        $universitas = Universitas::findOrFail($id);
        $universitas->update($data);

        return $universitas->fresh();
    }

    /**
     * Delete a universitas record.
     */
    public function deleteUniversitas(int $id): bool
    {
        $universitas = Universitas::findOrFail($id);

        return $universitas->delete();
    }

    // ═══════════════════════════════════════════════════════════
    //  MITRA PERUSAHAAN
    // ═══════════════════════════════════════════════════════════

    /**
     * Get all perusahaan, optionally filtered by search term.
     */
    public function getAllPerusahaan(?string $search = null): Collection
    {
        $query = Perusahaan::query()
            ->with('kota')
            ->orderByDesc('id_perusahaan');

        if ($search) {
            $query->where('nama_perusahaan', 'like', "%{$search}%");
        }

        return $query->get();
    }

    /**
     * Find a single perusahaan by ID.
     */
    public function findPerusahaan(int $id): ?Perusahaan
    {
        return Perusahaan::with('kota')->find($id);
    }

    /**
     * Create a new perusahaan record.
     */
    public function createPerusahaan(array $data): Perusahaan
    {
        return Perusahaan::create($data);
    }

    /**
     * Update an existing perusahaan record.
     */
    public function updatePerusahaan(int $id, array $data): Perusahaan
    {
        $perusahaan = Perusahaan::findOrFail($id);
        $perusahaan->update($data);

        return $perusahaan->fresh();
    }

    /**
     * Delete a perusahaan record.
     */
    public function deletePerusahaan(int $id): bool
    {
        $perusahaan = Perusahaan::findOrFail($id);

        return $perusahaan->delete();
    }
}
