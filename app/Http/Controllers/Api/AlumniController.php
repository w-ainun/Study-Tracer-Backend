<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\CareerStatusRequest;
use App\Http\Resources\AlumniResource;
use App\Http\Resources\RiwayatStatusResource;
use App\Services\AlumniService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class AlumniController extends Controller
{
    use ApiResponse;

    private AlumniService $alumniService;

    public function __construct(AlumniService $alumniService)
    {
        $this->alumniService = $alumniService;
    }

    public function profile(Request $request)
    {
        try {
            $alumni = $this->alumniService->getProfile($request->user()->id_users);

            if (!$alumni) {
                return $this->notFoundResponse('Profil alumni tidak ditemukan');
            }

            return $this->successResponse(new AlumniResource($alumni));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil profil: ' . $e->getMessage());
        }
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        try {
            $foto = $request->hasFile('foto') ? $request->file('foto') : null;
            $alumni = $this->alumniService->updateProfile(
                $request->user()->id_users,
                $request->validated(),
                $foto
            );

            return $this->successResponse(new AlumniResource($alumni), 'Profil berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui profil: ' . $e->getMessage());
        }
    }

    public function updateCareerStatus(CareerStatusRequest $request)
    {
        try {
            $riwayat = $this->alumniService->updateCareerStatus(
                $request->user()->id_users,
                $request->validated()
            );

            return $this->createdResponse(new RiwayatStatusResource($riwayat), 'Status karir berhasil disimpan');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menyimpan status karir: ' . $e->getMessage());
        }
    }
}
