<?php

namespace App\Repositories;

use App\Interfaces\PengumumanRepositoryInterface;
use App\Models\Pengumuman;
use Illuminate\Support\Facades\Cache;

class PengumumanRepository implements PengumumanRepositoryInterface
{
    /**
     * Get all pengumuman with filters, search, and pagination.
     * Pinned items always appear first, then sorted by newest.
     */
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = Pengumuman::with('user:id_users,email_users,role');

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Search by judul or konten
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                  ->orWhere('konten', 'like', "%{$search}%");
            });
        }

        // Always order: pinned first, then newest
        $query->orderByDesc('is_pinned')
              ->orderByDesc('created_at');

        return $query->paginate($perPage);
    }

    /**
     * Get single pengumuman by ID with author info.
     */
    public function getById(int $id)
    {
        return Pengumuman::with('user:id_users,email_users,role')
            ->findOrFail($id);
    }

    /**
     * Create a new pengumuman.
     */
    public function create(array $data)
    {
        $pengumuman = Pengumuman::create($data);
        Cache::forget('pengumuman.stats');
        return $pengumuman->load('user:id_users,email_users,role');
    }

    /**
     * Update an existing pengumuman.
     */
    public function update(int $id, array $data)
    {
        $pengumuman = Pengumuman::findOrFail($id);
        $pengumuman->update($data);
        Cache::forget('pengumuman.stats');
        return $pengumuman->fresh()->load('user:id_users,email_users,role');
    }

    /**
     * Delete a pengumuman.
     */
    public function delete(int $id)
    {
        $pengumuman = Pengumuman::findOrFail($id);
        $pengumuman->delete();
        Cache::forget('pengumuman.stats');
        return true;
    }

    /**
     * Toggle the pinned status of a pengumuman.
     */
    public function togglePin(int $id)
    {
        $pengumuman = Pengumuman::findOrFail($id);
        $pengumuman->update(['is_pinned' => !$pengumuman->is_pinned]);
        return $pengumuman->fresh()->load('user:id_users,email_users,role');
    }

    /**
     * Get status counts for sidebar statistics (cached).
     */
    public function getStatusCounts(): array
    {
        return Cache::remember('pengumuman.stats', 300, function () {
            return [
                'total'    => Pengumuman::count(),
                'aktif'    => Pengumuman::where('status', 'aktif')->count(),
                'draft'    => Pengumuman::where('status', 'draft')->count(),
                'berakhir' => Pengumuman::where('status', 'berakhir')->count(),
                'pinned'   => Pengumuman::where('is_pinned', true)->count(),
            ];
        });
    }
}
