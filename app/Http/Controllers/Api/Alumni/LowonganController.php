<?php

namespace App\Http\Controllers\Api\Alumni;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLowonganRequest;
use App\Http\Resources\Alumni\LowonganAlumniResource;
use App\Http\Resources\Alumni\MyLowonganResource;
use App\Http\Resources\Alumni\SavedLowonganResource;
use App\Services\Alumni\LowonganAlumniService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class LowonganController extends Controller
{
    use ApiResponse;

    private LowonganAlumniService $lowonganService;

    public function __construct(LowonganAlumniService $lowonganService)
    {
        $this->lowonganService = $lowonganService;
    }

    /**
     * GET /alumni/lowongan
     * Published lowongan sorted by skill match, with saved-state per item.
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['search', 'tipe_pekerjaan']);
            $perPage = $request->input('per_page', 15);

            $result = $this->lowonganService->getPublishedForAlumni(
                $request->user()->id_users,
                $filters,
                $perPage
            );

            $paginator = $result['lowongan'];

            // Flatten pagination so frontend can access last_page, current_page directly
            return $this->successResponse([
                'data'         => LowonganAlumniResource::collection($paginator),
                'saved_ids'    => $result['saved_ids'],
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data lowongan: ' . $e->getMessage());
        }
    }

    /**
     * GET /alumni/lowongan/{id}
     * Detail of a single published lowongan with is_saved flag.
     */
    public function show(Request $request, int $id)
    {
        try {
            $result = $this->lowonganService->getDetail($request->user()->id_users, $id);

            return $this->successResponse(
                (new LowonganAlumniResource($result['lowongan']))->additional(['is_saved' => $result['is_saved']])
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Lowongan tidak ditemukan', 404);
        }
    }

    /**
     * POST /alumni/lowongan
     * Alumni submits a new lowongan → status=draft, approval_status=pending.
     * Admin must approve before it goes live.
     */
    public function store(StoreLowonganRequest $request)
    {
        try {
            $data = $request->validated();

            // Handle foto upload
            if ($request->hasFile('foto_lowongan')) {
                $data['foto_lowongan'] = $request->file('foto_lowongan')->store('lowongan', 'public');
            }

            // Attach current alumni as poster
            $data['id_users'] = $request->user()->id_users;

            // Extract id_kota for perusahaan creation (frontend sends it)
            if ($request->has('id_kota')) {
                $data['id_kota'] = $request->input('id_kota');
            }

            $lowongan = $this->lowonganService->createLowongan($data);

            return $this->createdResponse(
                new MyLowonganResource($lowongan),
                'Lowongan berhasil diajukan. Menunggu persetujuan admin.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengajukan lowongan: ' . $e->getMessage());
        }
    }

    /**
     * GET /alumni/my-lowongan
     * Alumni's own lowongan submissions with approval status & progress.
     */
    public function myLowongan(Request $request)
    {
        try {
            $filters = $request->only(['status', 'approval_status', 'search']);
            $perPage = $request->input('per_page', 15);

            $paginator = $this->lowonganService->getMyLowongan(
                $request->user()->id_users,
                $filters,
                $perPage
            );

            return $this->successResponse([
                'data'         => MyLowonganResource::collection($paginator),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil lowongan saya: ' . $e->getMessage());
        }
    }

    /**
     * GET /alumni/saved-lowongan
     * Lowongan saved by the current alumni.
     */
    public function saved(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $paginator = $this->lowonganService->getSavedLowongan($request->user()->id_users, $perPage);

            // Flatten pagination so frontend can access last_page, current_page directly
            return $this->successResponse([
                'data'         => SavedLowonganResource::collection($paginator),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil lowongan tersimpan');
        }
    }

    /**
     * POST /alumni/lowongan/{id}/toggle-save
     * Toggle bookmark a lowongan.
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
