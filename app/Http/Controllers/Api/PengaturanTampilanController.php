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
            $fields = [
                'nama_sekolah',
                'primary_color',
                'secondary_color',
                'third_color',
                // Footer & contact
                'deskripsi_footer',
                'email_kontak',
                'web_kontak',
                'telp_kontak',
                // Modal texts
                'teks_privasi',
                'teks_layanan',
                'teks_dukungan',
                // Landing page content
                'landing_title',
                'landing_description',
            ];
            $data = [];

            foreach ($fields as $field) {
                if ($request->filled($field)) {
                    $data[$field] = $request->input($field);
                }
                // Allow explicitly setting nullable text fields to null/empty
                elseif ($request->has($field) && $request->input($field) === null) {
                    $data[$field] = null;
                }
            }

            // Handle removal flags
            if ($request->filled('remove_logo')) {
                $data['remove_logo'] = $request->boolean('remove_logo');
            }
            if ($request->filled('remove_login_bg')) {
                $data['remove_login_bg'] = $request->boolean('remove_login_bg');
            }
            if ($request->filled('remove_landing_bg')) {
                $data['remove_landing_bg'] = $request->boolean('remove_landing_bg');
            }

            // Handle images — can be file upload OR base64 data URL string
            $logo      = $request->file('logo');
            $loginBg   = $request->file('login_bg');
            $landingBg = $request->file('landing_bg');

            // If images were sent as base64 strings (data URL from frontend)
            $logoBase64      = null;
            $loginBgBase64   = null;
            $landingBgBase64 = null;

            if (!$logo && $request->filled('logo') && str_starts_with($request->input('logo'), 'data:image')) {
                $logoBase64 = $request->input('logo');
            }

            if (!$loginBg && $request->filled('login_bg') && str_starts_with($request->input('login_bg'), 'data:image')) {
                $loginBgBase64 = $request->input('login_bg');
            }

            if (!$landingBg && $request->filled('landing_bg') && str_starts_with($request->input('landing_bg'), 'data:image')) {
                $landingBgBase64 = $request->input('landing_bg');
            }

            $settings = $this->service->update(
                $data,
                $logo,
                $loginBg,
                $landingBg,
                $logoBase64,
                $loginBgBase64,
                $landingBgBase64
            );

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
