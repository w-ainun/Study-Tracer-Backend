<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnswerKuesionerRequest;
use App\Http\Requests\StoreKuesionerRequest;
use App\Http\Requests\StorePertanyaanRequest;
use App\Http\Requests\UpdatePertanyaanRequest;
use App\Http\Requests\UpdateKuesionerRequest;
use App\Http\Requests\UpdateKuesionerStatusRequest;
use App\Http\Resources\KuesionerResource;
use App\Http\Resources\PertanyaanResource;
use App\Services\KuesionerService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class KuesionerController extends Controller
{
    use ApiResponse;

    private KuesionerService $kuesionerService;

    public function __construct(KuesionerService $kuesionerService)
    {
        $this->kuesionerService = $kuesionerService;
    }

    /**
     * Get all kuesioner (admin view) — supports filters: id_status, search, status
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['id_status', 'search', 'status']);

            $perPage = $request->input('per_page', 15);
            $kuesioner = $this->kuesionerService->getAll($filters, $perPage);
            return $this->successResponse(KuesionerResource::collection($kuesioner)->response()->getData(true));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data kuesioner: ' . $e->getMessage());
        }
    }

    /**
     * Get published kuesioner (alumni/public view)
     */
    public function published(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $kuesioner = $this->kuesionerService->getPublished($perPage);
            return $this->successResponse(KuesionerResource::collection($kuesioner)->response()->getData(true));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data kuesioner: ' . $e->getMessage());
        }
    }

    /**
     * Get published kuesioner with filters (alumni view) — supports filters: id_status, search
     */
    public function indexForAlumni(Request $request)
    {
        try {
            $filters = $request->only(['id_status', 'search']);
            $perPage = $request->input('per_page', 15);
            $kuesioner = $this->kuesionerService->getAllPublished($filters, $perPage);
            return $this->successResponse(KuesionerResource::collection($kuesioner)->response()->getData(true));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data kuesioner: ' . $e->getMessage());
        }
    }

    /**
     * Get published kuesioner by status (e.g., kuesioner for "Bekerja")
     * Returns all matching kuesioner — both status-specific AND global (id_status IS NULL)
     */
    public function publishedByStatus(int $statusId)
    {
        try {
            $kuesioner = $this->kuesionerService->getPublishedByStatus($statusId);
            if ($kuesioner->isEmpty()) {
                return $this->notFoundResponse('Kuesioner untuk status ini belum tersedia');
            }
            return $this->successResponse(KuesionerResource::collection($kuesioner));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil kuesioner berdasarkan status: ' . $e->getMessage());
        }
    }

    /**
     * Get kuesioner detail (admin)
     */
    public function show(int $id)
    {
        try {
            $kuesioner = $this->kuesionerService->getById($id);
            return $this->successResponse(new KuesionerResource($kuesioner));
        } catch (\Exception $e) {
            return $this->errorResponse('Kuesioner tidak ditemukan', 404);
        }
    }

    /**
     * Get all pertanyaan with filters (admin)
     */
    public function getAllPertanyaan(Request $request)
    {
        try {
            $filters = $request->only(['id_kuesioner', 'search']);
            $perPage = $request->input('per_page', 15);
            $pertanyaan = $this->kuesionerService->getAllPertanyaan($filters, $perPage);
            return $this->successResponse(PertanyaanResource::collection($pertanyaan)->response()->getData(true));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data pertanyaan');
        }
    }

    /**
     * Get kuesioner with all pertanyaan & opsi jawaban (for alumni filling out)
     */
    public function showWithPertanyaan(int $id)
    {
        try {
            $kuesioner = $this->kuesionerService->getWithPertanyaan($id);
            return $this->successResponse(new KuesionerResource($kuesioner));
        } catch (\Exception $e) {
            return $this->errorResponse('Kuesioner tidak ditemukan', 404);
        }
    }

    /**
     * Create kuesioner (admin)
     */
    public function store(StoreKuesionerRequest $request)
    {
        try {
            $kuesioner = $this->kuesionerService->create($request->validated());
            return $this->createdResponse(new KuesionerResource($kuesioner), 'Kuesioner berhasil dibuat');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal membuat kuesioner: ' . $e->getMessage());
        }
    }

    /**
     * Update kuesioner (admin)
     */
    public function update(UpdateKuesionerRequest $request, int $id)
    {
        try {
            $kuesioner = $this->kuesionerService->update($id, $request->validated());
            return $this->successResponse(new KuesionerResource($kuesioner), 'Kuesioner berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui kuesioner: ' . $e->getMessage());
        }
    }

    /**
     * Delete kuesioner (admin)
     */
    public function destroy(int $id)
    {
        try {
            $this->kuesionerService->delete($id);
            return $this->successResponse(null, 'Kuesioner berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus kuesioner: ' . $e->getMessage());
        }
    }

    /**
     * Update kuesioner visibility status (hidden/aktif/draft)
     */
    public function updateStatus(UpdateKuesionerStatusRequest $request, int $id)
    {
        try {
            $kuesioner = $this->kuesionerService->updateKuesionerStatus($id, $request->validated()['status']);
            return $this->successResponse(new KuesionerResource($kuesioner), 'Status kuesioner berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui status kuesioner: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════
    //  PERTANYAAN
    // ═══════════════════════════════════════════════

    /**
     * Add pertanyaan to kuesioner
     */
    public function addPertanyaan(StorePertanyaanRequest $request, int $kuesionerId)
    {
        try {
            $data = $request->validated();
            $pertanyaan = $this->kuesionerService->addPertanyaan($kuesionerId, $data);
            return $this->createdResponse(new PertanyaanResource($pertanyaan), 'Pertanyaan berhasil ditambahkan');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan pertanyaan: ' . $e->getMessage());
        }
    }

    /**
     * Update pertanyaan
     */
    public function updatePertanyaan(UpdatePertanyaanRequest $request, int $kuesionerId, int $pertanyaanId)
    {
        try {
            $pertanyaan = $this->kuesionerService->updatePertanyaan($pertanyaanId, $request->validated());
            return $this->successResponse(new PertanyaanResource($pertanyaan), 'Pertanyaan berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui pertanyaan: ' . $e->getMessage());
        }
    }

    /**
     * Delete pertanyaan
     */
    public function deletePertanyaan(int $kuesionerId, int $pertanyaanId)
    {
        try {
            $this->kuesionerService->deletePertanyaan($pertanyaanId);
            return $this->successResponse(null, 'Pertanyaan berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus pertanyaan: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════
    //  JAWABAN
    // ═══════════════════════════════════════════════

    /**
     * Submit answers for a kuesioner (alumni)
     */
    public function submitAnswers(AnswerKuesionerRequest $request, int $kuesionerId)
    {
        try {
            $data = $request->validated();
            $this->kuesionerService->submitJawaban(
                $request->user()->id_users,
                $data['jawaban']
            );
            return $this->successResponse(null, 'Jawaban berhasil disimpan');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menyimpan jawaban: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════
    //  ADMIN – JAWABAN VIEWING
    // ═══════════════════════════════════════════════

    /**
     * List alumni who answered a kuesioner (admin)
     */
    public function listJawaban(Request $request, int $kuesionerId)
    {
        try {
            $filters = $request->only(['search']);
            $data = $this->kuesionerService->getAlumniJawaban($kuesionerId, $filters);
            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data jawaban: ' . $e->getMessage());
        }
    }

    /**
     * Get detail jawaban from a specific alumni (admin)
     */
    public function jawabanDetail(int $kuesionerId, int $alumniId)
    {
        try {
            $data = $this->kuesionerService->getAlumniJawabanDetail($kuesionerId, $alumniId);
            return $this->successResponse($data, 'Detail jawaban berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil detail jawaban: ' . $e->getMessage(), 404);
        }
    }

    // ═══════════════════════════════════════════════
    //  STATISTICS
    // ═══════════════════════════════════════════════

    /**
     * Get statistics for questionnaire responses (admin)
     */
    public function statistics(int $kuesionerId)
    {
        try {
            $data = $this->kuesionerService->getStatistics($kuesionerId);
            return $this->successResponse($data, 'Statistik kuesioner berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil statistik kuesioner: ' . $e->getMessage(), 404);
        }
    }
}
