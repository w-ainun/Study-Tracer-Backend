<?php

namespace App\Http\Controllers\Api\Alumni;

use App\Http\Controllers\Controller;
use App\Http\Resources\Alumni\AlumniDirectoryResource;
use App\Http\Resources\Alumni\PublicProfileResource;
use App\Services\Alumni\AlumniDirectoryService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class AlumniDirectoryController extends Controller
{
    use ApiResponse;

    private AlumniDirectoryService $directoryService;

    public function __construct(AlumniDirectoryService $directoryService)
    {
        $this->directoryService = $directoryService;
    }

    /**
     * GET /alumni/directory
     * Paginated alumni directory with search + filters.
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['search', 'tahun', 'status', 'universitas']);
            $perPage = $request->input('per_page', 12);

            $paginated = $this->directoryService->getAlumniDirectory($filters, $perPage);

            // Build flat response matching frontend expectations
            $items = AlumniDirectoryResource::collection($paginated);

            return $this->successResponse([
                'data'         => $items,
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data direktori alumni: ' . $e->getMessage());
        }
    }

    /**
     * GET /alumni/directory/filters
     * Filter options for directory dropdowns (tahun, status, universitas).
     */
    public function filterOptions()
    {
        try {
            $options = $this->directoryService->getFilterOptions();

            return $this->successResponse($options);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil opsi filter: ' . $e->getMessage());
        }
    }

    /**
     * GET /alumni/directory/{id}
     * Public profile of a single alumni — no sensitive data (email, no_hp, alamat, nis, nisn).
     */
    public function show(int $id)
    {
        try {
            $alumni = $this->directoryService->getAlumniPublicProfile($id);

            return $this->successResponse(new PublicProfileResource($alumni));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Alumni tidak ditemukan', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil profil alumni: ' . $e->getMessage());
        }
    }
}
