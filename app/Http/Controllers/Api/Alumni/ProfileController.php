<?php

namespace App\Http\Controllers\Api\Alumni;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\CareerStatusRequest;
use App\Http\Resources\Alumni\ProfileResource;
use App\Http\Resources\Alumni\ProfileRiwayatResource;
use App\Services\Alumni\ProfileService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use ApiResponse;

    private ProfileService $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * GET /alumni/profile
     * Full alumni profile (personal info, career history, skills, social links).
     */
    public function show(Request $request)
    {
        try {
            $alumni = $this->profileService->getProfile($request->user()->id_users);

            if (!$alumni) {
                return $this->notFoundResponse('Profil alumni tidak ditemukan');
            }

            return $this->successResponse(new ProfileResource($alumni));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil profil: ' . $e->getMessage());
        }
    }

    /**
     * PUT /alumni/profile
     * Update personal profile data (nama, alamat, foto, skills, social media).
     */
    public function update(UpdateProfileRequest $request)
    {
        try {
            $foto = $request->hasFile('foto') ? $request->file('foto') : null;
            $alumni = $this->profileService->updateProfile(
                $request->user()->id_users,
                $request->validated(),
                $foto
            );

            return $this->successResponse(
                new ProfileResource($alumni),
                'Pembaruan profil berhasil dikirim, menunggu persetujuan admin.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui profil: ' . $e->getMessage());
        }
    }

    /**
     * POST /alumni/career-status
     * Add new career status. Sets status_create = 'pending' for admin re-approval.
     */
    public function updateCareerStatus(CareerStatusRequest $request)
    {
        try {
            $riwayat = $this->profileService->updateCareerStatus(
                $request->user()->id_users,
                $request->validated()
            );

            return $this->createdResponse(
                new ProfileRiwayatResource($riwayat),
                'Status karir berhasil disimpan. Menunggu persetujuan admin.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menyimpan status karir: ' . $e->getMessage());
        }
    }

    /**
     * PUT /alumni/career-status/{id}
     * Update existing career status directly (e.g., update tahun_selesai).
     * Updates the existing riwayat_status without creating a new pending entry.
     */
    public function updateExistingCareerStatus(CareerStatusRequest $request, $id)
    {
        try {
            $riwayat = $this->profileService->updateExistingCareerStatus(
                $request->user()->id_users,
                $id,
                $request->validated()
            );

            return $this->successResponse(
                new ProfileRiwayatResource($riwayat),
                'Status karir berhasil diperbarui.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui status karir: ' . $e->getMessage());
        }
    }

    /**
     * PUT /alumni/profile/skills
     * Update skills (creates pending update for admin approval)
     */
    public function updateSkills(Request $request)
    {
        try {
            $request->validate([
                'skills' => 'required|array',
                'skills.*' => 'integer|exists:skills,id_skills',
            ]);

            $alumni = $this->profileService->updateSkills(
                $request->user()->id_users,
                $request->skills
            );

            return $this->successResponse(
                new ProfileResource($alumni),
                'Perubahan keahlian telah dikirim, menunggu persetujuan admin.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui keahlian: ' . $e->getMessage());
        }
    }

    /**
     * PUT /alumni/profile/pending-skills/{pendingId}
     * Update pending skills request
     */
    public function updatePendingSkills(Request $request, $pendingId)
    {
        try {
            $request->validate([
                'skills' => 'required|array',
                'skills.*' => 'integer|exists:skills,id_skills',
            ]);

            $alumni = $this->profileService->updatePendingSkills(
                $request->user()->id_users,
                $pendingId,
                $request->skills
            );

            return $this->successResponse(
                new ProfileResource($alumni),
                'Perubahan keahlian yang pending berhasil diperbarui.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui keahlian: ' . $e->getMessage());
        }
    }

    /**
     * DELETE /alumni/profile/pending-skills/{pendingId}
     * Cancel pending skills update
     */
    public function cancelPendingSkills(Request $request, $pendingId)
    {
        try {
            $alumni = $this->profileService->cancelPendingSkills(
                $request->user()->id_users,
                $pendingId
            );

            return $this->successResponse(
                new ProfileResource($alumni),
                'Pengajuan perubahan keahlian berhasil dibatalkan.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal membatalkan perubahan: ' . $e->getMessage());
        }
    }
}
