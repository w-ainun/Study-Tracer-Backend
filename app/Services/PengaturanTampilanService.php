<?php

namespace App\Services;

use App\Interfaces\PengaturanTampilanRepositoryInterface;
use App\Models\PengaturanTampilan;
use App\Traits\GeneratesThumbnail;
use Illuminate\Http\UploadedFile;

class PengaturanTampilanService
{
    use GeneratesThumbnail;

    private PengaturanTampilanRepositoryInterface $repository;

    public function __construct(PengaturanTampilanRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get current display settings.
     */
    public function get(): PengaturanTampilan
    {
        return $this->repository->get();
    }

    /**
     * Update display settings with optional file uploads.
     *
     * @param  array              $data     Validated settings data (nama_sekolah, colors).
     * @param  UploadedFile|null  $logo     New logo file (PNG, max 2MB).
     * @param  UploadedFile|null  $loginBg  New login background file (JPG/PNG, max 5MB).
     */
    public function update(
        array $data,
        ?UploadedFile $logo = null,
        ?UploadedFile $loginBg = null
    ): PengaturanTampilan {
        $existing = $this->repository->get();

        // Handle logo upload
        if ($logo) {
            // Delete old logo if exists
            if ($existing->logo) {
                $this->deleteWithThumbnail($existing->logo);
            }

            $result = $this->storeWithThumbnail($logo, 'pengaturan/logo', 200, 200);
            $data['logo'] = $result['path'];
        }

        // Handle explicit logo removal
        if (!empty($data['remove_logo'])) {
            if ($existing->logo) {
                $this->deleteWithThumbnail($existing->logo);
            }
            $data['logo'] = null;
            unset($data['remove_logo']);
        }

        // Handle login background upload
        if ($loginBg) {
            // Delete old login_bg if exists
            if ($existing->login_bg) {
                $this->deleteWithThumbnail($existing->login_bg);
            }

            $result = $this->storeWithThumbnail($loginBg, 'pengaturan/login_bg', 400, 300);
            $data['login_bg'] = $result['path'];
        }

        // Handle explicit login_bg removal
        if (!empty($data['remove_login_bg'])) {
            if ($existing->login_bg) {
                $this->deleteWithThumbnail($existing->login_bg);
            }
            $data['login_bg'] = null;
            unset($data['remove_login_bg']);
        }

        return $this->repository->update($data);
    }
}
