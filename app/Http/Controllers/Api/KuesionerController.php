<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnswerKuesionerRequest;
use App\Http\Requests\StoreKuesionerRequest;
use App\Http\Requests\StorePertanyaanRequest;
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
     * Get all kuesioner (admin view)
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['status_kuesioner', 'search']);
            // Map 'status' query param to 'status_kuesioner' for convenience
            if ($request->has('status') && !$request->has('status_kuesioner')) {
                $filters['status_kuesioner'] = $request->input('status');
            }
            $perPage = $request->input('per_page', 15);
            $kuesioner = $this->kuesionerService->getAll($filters, $perPage);
            return $this->successResponse(KuesionerResource::collection($kuesioner)->response()->getData(true));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data kuesioner');
        }
    }

    /**
     * Get published kuesioner (alumni view)
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
     * Get kuesioner with all pertanyaan & opsi jawaban (for filling out)
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

    public function store(StoreKuesionerRequest $request)
    {
        try {
            $kuesioner = $this->kuesionerService->create($request->validated());
            return $this->createdResponse(new KuesionerResource($kuesioner), 'Kuesioner berhasil dibuat');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal membuat kuesioner: ' . $e->getMessage());
        }
    }

    public function update(UpdateKuesionerRequest $request, int $id)
    {
        try {
            $kuesioner = $this->kuesionerService->update($id, $request->validated());
            return $this->successResponse(new KuesionerResource($kuesioner), 'Kuesioner berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui kuesioner: ' . $e->getMessage());
        }
    }

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
    public function updatePertanyaan(StorePertanyaanRequest $request, int $kuesionerId, int $pertanyaanId)
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

    /**
     * Submit answers for a kuesioner
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

    /**
     * Update pertanyaan visibility status (admin)
     */
    public function updatePertanyaanStatus(Request $request, int $kuesionerId, int $pertanyaanId)
    {
        $request->validate([
            'status_pertanyaan' => 'required|in:TERLIHAT,TERSEMBUNYI,DRAF',
        ]);

        try {
            $pertanyaan = $this->kuesionerService->updatePertanyaanStatus(
                $pertanyaanId,
                $request->input('status_pertanyaan')
            );
            return $this->successResponse(
                new PertanyaanResource($pertanyaan),
                'Status pertanyaan berhasil diperbarui'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui status pertanyaan: ' . $e->getMessage());
        }
    }
}
