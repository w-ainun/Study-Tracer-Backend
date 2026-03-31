<?php

namespace App\Services;

use App\Interfaces\PengumumanRepositoryInterface;
use App\Traits\GeneratesThumbnail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PengumumanService
{
    use GeneratesThumbnail;

    private PengumumanRepositoryInterface $pengumumanRepository;

    public function __construct(PengumumanRepositoryInterface $pengumumanRepository)
    {
        $this->pengumumanRepository = $pengumumanRepository;
    }

    // ── Read ─────────────────────────────────────

    public function getAll(array $filters = [], int $perPage = 15)
    {
        return $this->pengumumanRepository->getAll($filters, $perPage);
    }

    public function getById(int $id)
    {
        return $this->pengumumanRepository->getById($id);
    }

    public function getStatusCounts(): array
    {
        return $this->pengumumanRepository->getStatusCounts();
    }

    // ── Create ───────────────────────────────────

    public function create(array $data, ?UploadedFile $foto = null): mixed
    {
        if ($foto) {
            $result = $this->storeWithThumbnail($foto, 'pengumuman/foto', 400, 300);
            $data['foto'] = $result['path'];
        }

        return $this->pengumumanRepository->create($data);
    }

    // ── Update ───────────────────────────────────

    public function update(int $id, array $data, ?UploadedFile $foto = null): mixed
    {
        // Handle foto replacement
        if ($foto) {
            // Delete old foto if exists
            $existing = $this->pengumumanRepository->getById($id);
            if ($existing->foto) {
                $this->deleteWithThumbnail($existing->foto);
            }

            $result = $this->storeWithThumbnail($foto, 'pengumuman/foto', 400, 300);
            $data['foto'] = $result['path'];
        }

        return $this->pengumumanRepository->update($id, $data);
    }

    // ── Delete ───────────────────────────────────

    public function delete(int $id): bool
    {
        // Delete foto file before DB record
        $pengumuman = $this->pengumumanRepository->getById($id);
        if ($pengumuman->foto) {
            $this->deleteWithThumbnail($pengumuman->foto);
        }

        return $this->pengumumanRepository->delete($id);
    }

    // ── Toggle Pin ───────────────────────────────

    public function togglePin(int $id)
    {
        return $this->pengumumanRepository->togglePin($id);
    }
}
