<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLowonganRequest;
use App\Http\Requests\UpdateLowonganRequest;
use App\Http\Resources\LowonganResource;
use App\Models\Alumni;
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
                // Determine id_kota: try to match 'lokasi' with city name, or fallback to first city in DB
                $defaultCityId = \App\Models\Kota::value('id_kota') ?? 1; // Fallback to 1 if empty
                
                if (!empty($data['lokasi'])) {
                    $city = \App\Models\Kota::where('nama_kota', 'like', '%' . $data['lokasi'] . '%')->first();
                    if ($city) {
                        $defaultCityId = $city->id_kota;
                    }
                }

                $perusahaan = \App\Models\Perusahaan::firstOrCreate(
                    ['nama_perusahaan' => $data['nama_perusahaan']],
                    [
                        'jalan' => $data['lokasi'] ?? '-',
                        'id_kota' => $defaultCityId
                    ]
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
                $defaultCityId = \App\Models\Kota::value('id_kota') ?? 1;
                
                if (!empty($data['lokasi'])) {
                    $city = \App\Models\Kota::where('nama_kota', 'like', '%' . $data['lokasi'] . '%')->first();
                    if ($city) {
                        $defaultCityId = $city->id_kota;
                    }
                }

                $perusahaan = \App\Models\Perusahaan::firstOrCreate(
                    ['nama_perusahaan' => $data['nama_perusahaan']],
                    [
                        'jalan' => $data['lokasi'] ?? '-',
                        'id_kota' => $defaultCityId
                    ]
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

    public function repost(int $id)
    {
        try {
            $lowongan = $this->lowonganService->repost($id);
            return $this->successResponse(new LowonganResource($lowongan), 'Lowongan berhasil diposting ulang');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memposting ulang lowongan: ' . $e->getMessage());
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

    /**
     * Get published lowongan sorted by skill match for logged-in alumni.
     * Alumni with matching skills see relevant lowongan first.
     * Alumni without skills see random order.
     */
    public function publishedForAlumni(Request $request)
    {
        try {
            $filters = $request->only(['search']);
            $perPage = $request->input('per_page', 15);

            $user = $request->user();
            $alumni = Alumni::where('id_users', $user->id_users)->first();

            $alumniSkillIds = [];
            if ($alumni) {
                $alumniSkillIds = $alumni->skills()->pluck('skills.id_skills')->toArray();
            }

            $lowongan = $this->lowonganService->getPublishedForAlumni($alumniSkillIds, $filters, $perPage);

            return $this->successResponse(LowonganResource::collection($lowongan)->response()->getData(true));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data lowongan: ' . $e->getMessage());
        }
    }

    /**
     * Update status of a specific lowongan
     * Body: { "status": "closed" | "published" | "draft" }
     */
    public function updateStatus(Request $request, int $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:draft,published,closed'
            ]);

            $lowongan = $this->lowonganService->updateStatus($id, $request->status);
            return $this->successResponse(
                new LowonganResource($lowongan), 
                "Status lowongan berhasil diubah menjadi {$request->status}"
            );
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengubah status lowongan: ' . $e->getMessage());
        }
    }

    /**
     * Auto-close all expired lowongan (where deadline has passed)
     * This can be called manually or scheduled via cron
     */
    public function autoCloseExpired()
    {
        try {
            $count = $this->lowonganService->autoCloseExpired();
            return $this->successResponse(
                ['closed_count' => $count],
                "Berhasil menutup {$count} lowongan yang sudah expired"
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menutup lowongan expired: ' . $e->getMessage());
        }
    }
}
