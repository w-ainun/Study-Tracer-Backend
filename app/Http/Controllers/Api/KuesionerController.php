<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnswerKuesionerRequest;
use App\Http\Requests\StoreKuesionerRequest;
use App\Http\Requests\StorePertanyaanRequest;
use App\Http\Requests\StoreSectionQuesRequest;
use App\Http\Requests\UpdatePertanyaanRequest;
use App\Http\Requests\UpdateKuesionerRequest;
use App\Http\Resources\KuesionerResource;
use App\Http\Resources\JawabanKuesionerResource;
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
     * Get all kuesioner (admin view) — supports filters: id_status, search
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['id_status', 'search']);

            $perPage = $request->input('per_page', 15);
            $kuesioner = $this->kuesionerService->getAll($filters, $perPage);
            return $this->successResponse(KuesionerResource::collection($kuesioner)->response()->getData(true));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data kuesioner');
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
            return $this->errorResponse('Gagal mengambil data kuesioner');
        }
    }

    /**
     * Get published kuesioner by status (e.g., kuesioner for "Bekerja")
     */
    public function publishedByStatus(int $statusId)
    {
        try {
            $kuesioner = $this->kuesionerService->getPublishedByStatus($statusId);
            if (!$kuesioner) {
                return $this->notFoundResponse('Kuesioner untuk status ini belum tersedia');
            }
            return $this->successResponse(new KuesionerResource($kuesioner));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil kuesioner berdasarkan status');
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
            $filters = $request->only(['id_kuesioner', 'id_sectionques', 'search']);
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
     * Update kuesioner visibility status - DEPRECATED: status moved to pertanyaan level
     */
    public function updateStatus(Request $request, int $id)
    {
        return $this->errorResponse('Status kuesioner telah dipindahkan ke level pertanyaan. Gunakan update status_pertanyaan.', 400);
    }

    // ═══════════════════════════════════════════════
    //  PERTANYAAN
    // ═══════════════════════════════════════════════

    /**
     * Add pertanyaan to kuesioner (with auto section_ques)
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
     * Store pertanyaan directly (using id_sectionques from body)
     */
    public function storePertanyaan(StorePertanyaanRequest $request)
    {
        try {
            $data = $request->validated();
            
            // Validasi id_sectionques harus ada
            if (empty($data['id_sectionques'])) {
                return $this->errorResponse('id_sectionques wajib diisi', 422);
            }
            
            $pertanyaan = $this->kuesionerService->storePertanyaan($data);
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
    //  SECTION QUES
    // ═══════════════════════════════════════════════

    /**
     * Create section_ques (judul pertanyaan)
     */
    public function storeSectionQues(StoreSectionQuesRequest $request)
    {
        try {
            $section = $this->kuesionerService->createSectionQues($request->validated());
            return $this->createdResponse($section, 'Judul pertanyaan berhasil ditambahkan');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan judul pertanyaan: ' . $e->getMessage());
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

            // Transform jawaban using resource
            if (isset($data['jawaban'])) {
                $data['jawaban'] = JawabanKuesionerResource::collection($data['jawaban']);
            }

            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil detail jawaban: ' . $e->getMessage());
        }
    }
}
