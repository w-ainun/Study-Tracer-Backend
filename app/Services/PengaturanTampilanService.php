<?php

namespace App\Services;

use App\Interfaces\PengaturanTampilanRepositoryInterface;
use App\Models\PengaturanTampilan;
use App\Traits\GeneratesThumbnail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
     * Update display settings with optional file uploads or base64 images.
     * Saves a history snapshot BEFORE applying changes.
     *
     * @param  array              $data              Validated settings data (only fields that were sent).
     * @param  UploadedFile|null  $logo              New logo as file upload.
     * @param  UploadedFile|null  $loginBg           New login background as file upload.
     * @param  UploadedFile|null  $landingBg         New landing background as file upload.
     * @param  string|null        $logoBase64        New logo as base64 data URL string.
     * @param  string|null        $loginBgBase64     New login background as base64 data URL string.
     * @param  string|null        $landingBgBase64   New landing background as base64 data URL string.
     */
    public function update(
        array $data,
        ?UploadedFile $logo = null,
        ?UploadedFile $loginBg = null,
        ?UploadedFile $landingBg = null,
        ?string $logoBase64 = null,
        ?string $loginBgBase64 = null,
        ?string $landingBgBase64 = null
    ): PengaturanTampilan {
        $existing = $this->repository->get();

        // ── Save history snapshot BEFORE making any changes ──
        $adminId = Auth::id() ?? 0;
        $this->repository->saveHistory($existing, $adminId, 'update');

        // ── Handle logo ─────────────────────────────

        // Option A: Logo as file upload
        if ($logo) {
            if ($existing->logo) {
                $this->deleteWithThumbnail($existing->logo);
            }
            $result = $this->storeWithThumbnail($logo, 'pengaturan/logo', 200, 200);
            $data['logo'] = $result['path'];
        }
        // Option B: Logo as base64 data URL string
        elseif ($logoBase64) {
            if ($existing->logo) {
                $this->deleteWithThumbnail($existing->logo);
            }
            $data['logo'] = $this->storeBase64Image($logoBase64, 'pengaturan/logo');
        }

        // Handle explicit logo removal
        if (!empty($data['remove_logo'])) {
            if ($existing->logo) {
                $this->deleteWithThumbnail($existing->logo);
            }
            $data['logo'] = null;
            unset($data['remove_logo']);
        }

        // ── Handle login background ─────────────────

        // Option A: Login BG as file upload
        if ($loginBg) {
            if ($existing->login_bg) {
                $this->deleteWithThumbnail($existing->login_bg);
            }
            $result = $this->storeWithThumbnail($loginBg, 'pengaturan/login_bg', 400, 300);
            $data['login_bg'] = $result['path'];
        }
        // Option B: Login BG as base64 data URL string
        elseif ($loginBgBase64) {
            if ($existing->login_bg) {
                $this->deleteWithThumbnail($existing->login_bg);
            }
            $data['login_bg'] = $this->storeBase64Image($loginBgBase64, 'pengaturan/login_bg');
        }

        // Handle explicit login_bg removal
        if (!empty($data['remove_login_bg'])) {
            if ($existing->login_bg) {
                $this->deleteWithThumbnail($existing->login_bg);
            }
            $data['login_bg'] = null;
            unset($data['remove_login_bg']);
        }

        // ── Handle landing background ───────────────

        // Option A: Landing BG as file upload
        if ($landingBg) {
            if ($existing->landing_bg) {
                $this->deleteWithThumbnail($existing->landing_bg);
            }
            $result = $this->storeWithThumbnail($landingBg, 'pengaturan/landing_bg', 800, 450);
            $data['landing_bg'] = $result['path'];
        }
        // Option B: Landing BG as base64 data URL string
        elseif ($landingBgBase64) {
            if ($existing->landing_bg) {
                $this->deleteWithThumbnail($existing->landing_bg);
            }
            $data['landing_bg'] = $this->storeBase64Image($landingBgBase64, 'pengaturan/landing_bg');
        }

        // Handle explicit landing_bg removal
        if (!empty($data['remove_landing_bg'])) {
            if ($existing->landing_bg) {
                $this->deleteWithThumbnail($existing->landing_bg);
            }
            $data['landing_bg'] = null;
            unset($data['remove_landing_bg']);
        }

        // Remove all removal flags from data (not DB columns)
        unset($data['remove_logo'], $data['remove_login_bg'], $data['remove_landing_bg']);

        return $this->repository->update($data);
    }

    /**
     * Revert settings to the most recent history snapshot.
     *
     * Steps:
     * 1. Get the latest history snapshot
     * 2. Save current state as a new history entry (type: revert) for undo-undo
     * 3. Clean up current image files that differ from snapshot
     * 4. Apply snapshot data to settings
     * 5. Delete the consumed history entry
     *
     * @return PengaturanTampilan
     * @throws \RuntimeException If no history is available
     */
    public function revert(): PengaturanTampilan
    {
        $history = $this->repository->getLatestHistory();

        if (!$history) {
            throw new \RuntimeException('Tidak ada riwayat perubahan yang dapat dikembalikan.');
        }

        $existing = $this->repository->get();
        $snapshot = $history->snapshot;

        // Save current state before reverting (so the revert itself can be undone)
        $adminId = Auth::id() ?? 0;
        $this->repository->saveHistory($existing, $adminId, 'revert');

        // ── Handle image file changes ──
        // If the snapshot has different image paths, clean up current images
        // Note: We do NOT delete snapshot images — they're being restored
        $imageFields = ['logo', 'login_bg', 'landing_bg'];
        foreach ($imageFields as $field) {
            $currentPath  = $existing->{$field};
            $snapshotPath = $snapshot[$field] ?? null;

            // Current image exists but differs from snapshot → delete current
            if ($currentPath && $currentPath !== $snapshotPath) {
                $this->deleteWithThumbnail($currentPath);
            }
        }

        // Apply snapshot data
        $restoreData = [];
        foreach (PengaturanTampilan::SNAPSHOTABLE_FIELDS as $field) {
            if (array_key_exists($field, $snapshot)) {
                $restoreData[$field] = $snapshot[$field];
            }
        }

        // Delete the consumed history entry
        $this->repository->deleteHistory($history->id);

        return $this->repository->update($restoreData);
    }

    /**
     * Reset all settings to factory defaults.
     *
     * Steps:
     * 1. Save current state to history (type: reset) for undo
     * 2. Delete all current image files
     * 3. Apply factory default values
     *
     * @return PengaturanTampilan
     */
    public function resetToDefaults(): PengaturanTampilan
    {
        $existing = $this->repository->get();

        // Save current state before reset (so the reset can be undone via revert)
        $adminId = Auth::id() ?? 0;
        $this->repository->saveHistory($existing, $adminId, 'reset');

        // Delete all current image files
        $imageFields = ['logo', 'login_bg', 'landing_bg'];
        foreach ($imageFields as $field) {
            if ($existing->{$field}) {
                $this->deleteWithThumbnail($existing->{$field});
            }
        }

        return $this->repository->resetToDefaults();
    }

    /**
     * Decode a base64 data URL string and store it as a file.
     *
     * @param  string  $base64String  Format: "data:image/png;base64,iVBOR..."
     * @param  string  $directory     Storage subdirectory (e.g. 'pengaturan/logo').
     * @return string  The stored file path (relative to storage/public).
     */
    private function storeBase64Image(string $base64String, string $directory): string
    {
        // Extract mime type and data from data URL
        // Format: data:image/png;base64,iVBOR...
        if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $matches)) {
            $extension = $matches[1];
            // Normalize jpeg extension
            if ($extension === 'jpeg') {
                $extension = 'jpg';
            }

            // Remove the data URL prefix to get pure base64 data
            $base64Data = substr($base64String, strpos($base64String, ',') + 1);
            $decodedData = base64_decode($base64Data);

            if ($decodedData === false) {
                throw new \InvalidArgumentException('Format base64 tidak valid.');
            }

            // Generate unique filename
            $filename = Str::uuid() . '.' . $extension;
            $filePath = $directory . '/' . $filename;

            // Ensure directory exists and store
            Storage::disk('public')->makeDirectory($directory);
            Storage::disk('public')->put($filePath, $decodedData);

            return $filePath;
        }

        throw new \InvalidArgumentException('Format data URL gambar tidak valid.');
    }
}

