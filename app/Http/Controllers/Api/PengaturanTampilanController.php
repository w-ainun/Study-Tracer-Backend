<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePengaturanTampilanRequest;
use App\Http\Resources\PengaturanTampilanResource;
use App\Services\PengaturanTampilanService;
use App\Traits\ApiResponse;

class PengaturanTampilanController extends Controller
{
    use ApiResponse;

    private PengaturanTampilanService $service;

    public function __construct(PengaturanTampilanService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /admin/pengaturan-tampilan
     * GET /settings/tampilan (public)
     *
     * Retrieve current display settings.
     */
    public function show()
    {
        try {
            $settings = $this->service->get();

            return $this->successResponse(
                new PengaturanTampilanResource($settings)
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil pengaturan tampilan');
        }
    }

    /**
     * POST /admin/pengaturan-tampilan
     *
     * Update display settings (multipart/form-data).
     * Supports partial updates — only send fields you want to change.
     */
    public function update(UpdatePengaturanTampilanRequest $request)
    {
        try {
            $data = $request->only([
                'nama_sekolah',
                'primary_color',
                'secondary_color',
                'third_color',
                'remove_logo',
                'remove_login_bg',
            ]);

            $logo    = $request->file('logo');
            $loginBg = $request->file('login_bg');

            $settings = $this->service->update($data, $logo, $loginBg);

            return $this->successResponse(
                new PengaturanTampilanResource($settings),
                'Pengaturan tampilan berhasil diperbarui'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Gagal memperbarui pengaturan tampilan: ' . $e->getMessage()
            );
        }
    }
}
