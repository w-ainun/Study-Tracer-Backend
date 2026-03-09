<?php

namespace App\Services\Alumni;

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
     * Create portofolio
     */
    public function create(int $alumniId, array $data, ?UploadedFile $gambar = null): Portofolio
    {
        return DB::transaction(function () use ($alumniId, $data, $gambar) {
            // Handle image upload with thumbnail
            if ($gambar) {
                $result = $this->storeWithThumbnail($gambar, 'portofolio');
                $data['gambar'] = $result['path'];
            }

            return Portofolio::create([
                'id_alumni' => $alumniId,
                'judul' => $data['judul'],
                'deskripsi' => $data['deskripsi'] ?? null,
                'link_project' => $data['link_project'] ?? null,
                'gambar' => $data['gambar'] ?? null,
            ]);
        });
    }

    /**
     * Update portofolio
     */
    public function update(int $alumniId, int $id, array $data, ?UploadedFile $gambar = null): Portofolio
    {
        $portofolio = Portofolio::where('id_alumni', $alumniId)->findOrFail($id);

        return DB::transaction(function () use ($portofolio, $data, $gambar) {
            // Handle image upload with thumbnail
            if ($gambar) {
                // Delete old image
                if ($portofolio->gambar) {
                    $this->deleteWithThumbnail($portofolio->gambar);
                }

                $result = $this->storeWithThumbnail($gambar, 'portofolio');
                $data['gambar'] = $result['path'];
            }

            $portofolio->update([
                'judul' => $data['judul'] ?? $portofolio->judul,
                'deskripsi' => $data['deskripsi'] ?? $portofolio->deskripsi,
                'link_project' => $data['link_project'] ?? $portofolio->link_project,
                'gambar' => $data['gambar'] ?? $portofolio->gambar,
            ]);

            return $portofolio->fresh();
        });
    }

    /**
     * Delete portofolio
     */
    public function delete(int $alumniId, int $id): bool
    {
        $portofolio = Portofolio::where('id_alumni', $alumniId)->findOrFail($id);

        // Delete image if exists
        if ($portofolio->gambar) {
            $this->deleteWithThumbnail($portofolio->gambar);
        }

        return $portofolio->delete();
    }
}
