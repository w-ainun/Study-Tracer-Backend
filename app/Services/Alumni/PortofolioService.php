<?php

namespace App\Services\Alumni;

use App\Models\PendingProfileUpdate;
use App\Models\Portofolio;
use App\Traits\GeneratesThumbnail;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;

class PortofolioService
{
    use GeneratesThumbnail;

    /**
     * Get all portofolio for an alumni
     */
    public function getByAlumniId(int $alumniId): array
    {
        return Portofolio::where('id_alumni', $alumniId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Create portofolio — saves as pending for admin approval.
     */
    public function create(int $alumniId, array $data, ?UploadedFile $gambar = null): PendingProfileUpdate
    {
        return DB::transaction(function () use ($alumniId, $data, $gambar) {
            $gambarPath = null;
            if ($gambar) {
                try {
                    $result = $this->storeWithThumbnail($gambar, 'portofolio/pending');
                    $gambarPath = $result['path'];
                } catch (\Error $e) {
                    $gambarPath = $gambar->store('portofolio/pending', 'public');
                }
            }

            return PendingProfileUpdate::create([
                'id_alumni' => $alumniId,
                'section' => 'portofolio',
                'action' => 'create',
                'old_data' => null,
                'new_data' => [
                    'judul' => $data['judul'],
                    'deskripsi' => $data['deskripsi'] ?? null,
                    'link_project' => $data['link_project'] ?? null,
                    'gambar' => $gambarPath,
                ],
                'gambar_path' => $gambarPath,
            ]);
        });
    }

    /**
     * Update portofolio — saves as pending for admin approval.
     */
    public function update(int $alumniId, int $id, array $data, ?UploadedFile $gambar = null): PendingProfileUpdate
    {
        $portofolio = Portofolio::where('id_alumni', $alumniId)->findOrFail($id);

        return DB::transaction(function () use ($alumniId, $id, $portofolio, $data, $gambar) {
            // Check for existing pending update
            $existingPending = PendingProfileUpdate::where('id_alumni', $alumniId)
                ->where('section', 'portofolio')
                ->where('action', 'update')
                ->where('related_id', $id)
                ->where('status', 'pending')
                ->first();

            if ($existingPending) {
                throw new \Exception('Anda sudah memiliki pembaruan portofolio ini yang sedang menunggu persetujuan admin.');
            }

            $gambarPath = null;
            if ($gambar) {
                try {
                    $result = $this->storeWithThumbnail($gambar, 'portofolio/pending');
                    $gambarPath = $result['path'];
                } catch (\Error $e) {
                    $gambarPath = $gambar->store('portofolio/pending', 'public');
                }
            }

            return PendingProfileUpdate::create([
                'id_alumni' => $alumniId,
                'section' => 'portofolio',
                'action' => 'update',
                'related_id' => $id,
                'old_data' => [
                    'judul' => $portofolio->judul,
                    'deskripsi' => $portofolio->deskripsi,
                    'link_project' => $portofolio->link_project,
                    'gambar' => $portofolio->gambar,
                ],
                'new_data' => [
                    'judul' => $data['judul'] ?? $portofolio->judul,
                    'deskripsi' => $data['deskripsi'] ?? $portofolio->deskripsi,
                    'link_project' => $data['link_project'] ?? $portofolio->link_project,
                    'gambar' => $gambarPath ?? $portofolio->gambar,
                ],
                'gambar_path' => $gambarPath,
            ]);
        });
    }

    /**
     * Delete portofolio — saves as pending for admin approval.
     */
    public function delete(int $alumniId, int $id): PendingProfileUpdate
    {
        $portofolio = Portofolio::where('id_alumni', $alumniId)->findOrFail($id);

        // Check for existing pending delete
        $existingPending = PendingProfileUpdate::where('id_alumni', $alumniId)
            ->where('section', 'portofolio')
            ->where('action', 'delete')
            ->where('related_id', $id)
            ->where('status', 'pending')
            ->first();

        if ($existingPending) {
            throw new \Exception('Penghapusan portofolio ini sudah menunggu persetujuan admin.');
        }

        return PendingProfileUpdate::create([
            'id_alumni' => $alumniId,
            'section' => 'portofolio',
            'action' => 'delete',
            'related_id' => $id,
            'old_data' => [
                'judul' => $portofolio->judul,
                'deskripsi' => $portofolio->deskripsi,
                'link_project' => $portofolio->link_project,
                'gambar' => $portofolio->gambar,
            ],
            'new_data' => null,
        ]);
    }
}
