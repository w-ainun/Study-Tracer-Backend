<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PengumumanRequest;
use App\Http\Resources\PengumumanResource;
use App\Services\PengumumanService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PengumumanController extends Controller
{
    use ApiResponse;

    private PengumumanService $pengumumanService;

    public function __construct(PengumumanService $pengumumanService)
    {
        $this->pengumumanService = $pengumumanService;
    }

    /**
     * GET /admin/pengumuman
     * List all pengumuman with filters, search, and pagination.
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['status', 'search']);
            $perPage = $request->input('per_page', 15);
            $pengumuman = $this->pengumumanService->getAll($filters, $perPage);

            return $this->successResponse(
                PengumumanResource::collection($pengumuman)->response()->getData(true)
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data pengumuman');
        }
    }

    /**
     * GET /admin/pengumuman/stats
     * Sidebar status counts.
     */
    public function stats()
    {
        try {
            $stats = $this->pengumumanService->getStatusCounts();
            return $this->successResponse($stats);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil statistik pengumuman');
        }
    }

    /**
     * GET /admin/pengumuman/{id}
     * Show single pengumuman.
     */
    public function show(int $id)
    {
        try {
            $pengumuman = $this->pengumumanService->getById($id);
            return $this->successResponse(new PengumumanResource($pengumuman));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Pengumuman tidak ditemukan');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil detail pengumuman');
        }
    }

    /**
     * POST /admin/pengumuman
     * Create a new pengumuman (multipart/form-data).
     */
    public function store(PengumumanRequest $request)
    {
        try {
            $data = $request->only(['judul', 'konten', 'status']);
            $data['id_users'] = $request->user()->id_users;

            $foto = $request->file('foto');
            $pengumuman = $this->pengumumanService->create($data, $foto);

            return $this->createdResponse(
                new PengumumanResource($pengumuman),
                'Pengumuman berhasil dibuat'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal membuat pengumuman: ' . $e->getMessage());
        }
    }

    /**
     * PUT/POST /admin/pengumuman/{id}
     * Update an existing pengumuman (multipart/form-data).
     */
    public function update(PengumumanRequest $request, int $id)
    {
        try {
            $data = $request->only(['judul', 'konten', 'status']);

            // Handle explicit foto removal
            if ($request->has('remove_foto') && $request->input('remove_foto')) {
                $data['foto'] = null;
            }

            $foto = $request->file('foto');
            $pengumuman = $this->pengumumanService->update($id, $data, $foto);

            return $this->successResponse(
                new PengumumanResource($pengumuman),
                'Pengumuman berhasil diperbarui'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Pengumuman tidak ditemukan');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui pengumuman: ' . $e->getMessage());
        }
    }

    /**
     * DELETE /admin/pengumuman/{id}
     * Delete a pengumuman and its associated files.
     */
    public function destroy(int $id)
    {
        try {
            $this->pengumumanService->delete($id);
            return $this->successResponse(null, 'Pengumuman berhasil dihapus');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Pengumuman tidak ditemukan');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus pengumuman: ' . $e->getMessage());
        }
    }

    /**
     * PATCH /admin/pengumuman/{id}/pin
     * Toggle the pinned status.
     */
    public function togglePin(int $id)
    {
        try {
            $pengumuman = $this->pengumumanService->togglePin($id);
            $message = $pengumuman->is_pinned
                ? 'Pengumuman berhasil di-pin'
                : 'Pengumuman berhasil di-unpin';

            return $this->successResponse(
                new PengumumanResource($pengumuman),
                $message
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Pengumuman tidak ditemukan');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengubah status pin: ' . $e->getMessage());
        }
    }
    public function published(Request $request)
    {
    try {
        $filters = ['status' => 'aktif'];
        if ($request->has('search')) {
            $filters['search'] = $request->input('search');
        }
        $perPage = $request->input('per_page', 10);
        $pengumuman = $this->pengumumanService->getAll($filters, $perPage);
        return $this->successResponse(
            PengumumanResource::collection($pengumuman)->response()->getData(true)
        );
    } catch (\Exception $e) {
        return $this->errorResponse('Gagal mengambil data pengumuman');
    }
}
}
