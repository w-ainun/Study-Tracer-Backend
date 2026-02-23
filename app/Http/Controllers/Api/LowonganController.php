<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLowonganRequest;
use App\Http\Requests\UpdateLowonganRequest;
use App\Http\Resources\LowonganResource;
use App\Services\LowonganService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class LowonganController extends Controller
{
    use ApiResponse;

    private LowonganService $lowonganService;

    public function __construct(LowonganService $lowonganService)
    {
        $this->lowonganService = $lowonganService;
    }

    /**
     * Get all job listings (admin view, supports filters)
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['status', 'approval_status', 'search']);
            $perPage = $request->input('per_page', 15);
            $lowongan = $this->lowonganService->getAll($filters, $perPage);

            return $this->successResponse(LowonganResource::collection($lowongan)->response()->getData(true));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data lowongan');
        }
    }

    /**
     * Get published & approved job listings (public/alumni view)
     */
    public function published(Request $request)
    {
        try {
            $filters = $request->only(['search']);
            $perPage = $request->input('per_page', 15);
            $lowongan = $this->lowonganService->getApproved($filters, $perPage);

            return $this->successResponse(LowonganResource::collection($lowongan)->response()->getData(true));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data lowongan');
        }
    }

    public function show(int $id)
    {
        try {
            $lowongan = $this->lowonganService->getById($id);
            return $this->successResponse(new LowonganResource($lowongan));
        } catch (\Exception $e) {
            return $this->errorResponse('Lowongan tidak ditemukan', 404);
        }
    }

    public function store(StoreLowonganRequest $request)
    {
        try {
            $data = $request->validated();

            // Handle foto upload
            if ($request->hasFile('foto_lowongan')) {
                $data['foto_lowongan'] = $request->file('foto_lowongan')->store('lowongan', 'public');
            }

            // If nama_perusahaan provided but no id_perusahaan, auto-create
            if (!empty($data['nama_perusahaan']) && empty($data['id_perusahaan'])) {
                $perusahaan = \App\Models\Perusahaan::firstOrCreate(
                    ['nama_perusahaan' => $data['nama_perusahaan']],
                    ['jalan' => $data['lokasi'] ?? null]
                );
                $data['id_perusahaan'] = $perusahaan->id_perusahaan;
            }
            unset($data['nama_perusahaan']);

            // Attach current user as poster
            $data['id_users'] = $request->user()->id_users;

            $lowongan = $this->lowonganService->create($data);
            return $this->createdResponse(new LowonganResource($lowongan), 'Lowongan berhasil dibuat');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal membuat lowongan: ' . $e->getMessage());
        }
    }

    public function update(UpdateLowonganRequest $request, int $id)
    {
        try {
            $data = $request->validated();

            if ($request->hasFile('foto_lowongan')) {
                $data['foto_lowongan'] = $request->file('foto_lowongan')->store('lowongan', 'public');
            }

            // If nama_perusahaan provided, auto-create
            if (!empty($data['nama_perusahaan'])) {
                $perusahaan = \App\Models\Perusahaan::firstOrCreate(
                    ['nama_perusahaan' => $data['nama_perusahaan']],
                    ['jalan' => $data['lokasi'] ?? null]
                );
                $data['id_perusahaan'] = $perusahaan->id_perusahaan;
            }
            unset($data['nama_perusahaan']);

            $lowongan = $this->lowonganService->update($id, $data);
            return $this->successResponse(new LowonganResource($lowongan), 'Lowongan berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui lowongan: ' . $e->getMessage());
        }
    }

    public function destroy(int $id)
    {
        try {
            $this->lowonganService->delete($id);
            return $this->successResponse(null, 'Lowongan berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus lowongan: ' . $e->getMessage());
        }
    }

    /**
     * Get pending job listings for admin review
     */
    public function pending(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $lowongan = $this->lowonganService->getPending($perPage);
            return $this->successResponse(LowonganResource::collection($lowongan)->response()->getData(true));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data lowongan pending');
        }
    }

    public function approve(int $id)
    {
        try {
            $lowongan = $this->lowonganService->approve($id);
            return $this->successResponse(new LowonganResource($lowongan), 'Lowongan berhasil disetujui');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menyetujui lowongan: ' . $e->getMessage());
        }
    }

    public function reject(int $id)
    {
        try {
            $lowongan = $this->lowonganService->reject($id);
            return $this->successResponse(new LowonganResource($lowongan), 'Lowongan berhasil ditolak');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menolak lowongan: ' . $e->getMessage());
        }
    }

    /**
     * Get saved job listings for current user
     */
    public function savedByUser(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $saved = $this->lowonganService->getSavedByUser($request->user()->id_users, $perPage);
            return $this->successResponse($saved);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil lowongan tersimpan');
        }
    }

    /**
     * Toggle save/unsave a job listing
     */
    public function toggleSave(Request $request, int $id)
    {
        try {
            $saved = $this->lowonganService->toggleSave($request->user()->id_users, $id);
            $message = $saved ? 'Lowongan berhasil disimpan' : 'Lowongan berhasil dihapus dari simpanan';
            return $this->successResponse(['saved' => $saved], $message);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menyimpan lowongan: ' . $e->getMessage());
        }
    }
}
