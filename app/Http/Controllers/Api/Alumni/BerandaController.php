<?php

namespace App\Http\Controllers\Api\Alumni;

use App\Http\Controllers\Controller;
use App\Http\Resources\Alumni\BerandaResource;
use App\Http\Resources\Alumni\StatusPengajuanResource;
use App\Services\Alumni\BerandaService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class BerandaController extends Controller
{
    use ApiResponse;

    private BerandaService $berandaService;

    public function __construct(BerandaService $berandaService)
    {
        $this->berandaService = $berandaService;
    }

    /**
     * GET /alumni/beranda
     * Returns full dashboard data for alumni beranda page.
     */
    public function index(Request $request)
    {
        try {
            $data = $this->berandaService->getBerandaData($request->user()->id_users);

            return $this->successResponse(new BerandaResource($data));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data beranda: ' . $e->getMessage());
        }
    }

    /**
     * GET /alumni/status-pengajuan
     * Returns verification status timeline for the alumni.
     */
    public function statusPengajuan(Request $request)
    {
        try {
            $data = $this->berandaService->getStatusPengajuan($request->user()->id_users);

            return $this->successResponse(new StatusPengajuanResource($data));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil status pengajuan: ' . $e->getMessage());
        }
    }
}
