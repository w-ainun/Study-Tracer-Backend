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
     * Update display settings (multipart/form-data or JSON).
     * Supports partial updates — only send fields you want to change.
     * Images can be sent as file uploads OR as base64 data URL strings.
     */
    public function update(UpdatePengaturanTampilanRequest $request)
    {
        try {
            // Only collect fields that are actually present in the request
            // This ensures partial updates work correctly
            $fields = ['nama_sekolah', 'primary_color', 'secondary_color', 'third_color'];
            $data = [];

            foreach ($fields as $field) {
                if ($request->filled($field)) {
                    $data[$field] = $request->input($field);
                }
            }

            // Handle removal flags
            if ($request->filled('remove_logo')) {
                $data['remove_logo'] = $request->boolean('remove_logo');
            }
            if ($request->filled('remove_login_bg')) {
                $data['remove_login_bg'] = $request->boolean('remove_login_bg');
            }

            // Handle images — can be file upload OR base64 data URL string
            $logo    = $request->file('logo');
            $loginBg = $request->file('login_bg');

            // If logo was sent as a base64 string (data URL from frontend)
            $logoBase64    = null;
            $loginBgBase64 = null;

            if (!$logo && $request->filled('logo') && str_starts_with($request->input('logo'), 'data:image')) {
                $logoBase64 = $request->input('logo');
            }

            if (!$loginBg && $request->filled('login_bg') && str_starts_with($request->input('login_bg'), 'data:image')) {
                $loginBgBase64 = $request->input('login_bg');
            }

            $settings = $this->service->update($data, $logo, $loginBg, $logoBase64, $loginBgBase64);

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
