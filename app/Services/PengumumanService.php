<?php

namespace App\Services;

use App\Interfaces\PengumumanRepositoryInterface;
use App\Jobs\SendPengumumanNotifications;
use App\Models\Alumni;
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

        $pengumuman = $this->pengumumanRepository->create($data);

        // Kirim notifikasi ke semua alumni jika pengumuman langsung aktif
        if (($data['status'] ?? null) === 'aktif') {
            $this->notifyAllAlumni($pengumuman);
        }

        return $pengumuman;
    }

    // ── Update ───────────────────────────────────

    public function update(int $id, array $data, ?UploadedFile $foto = null): mixed
    {
        // Cek status lama sebelum update
        $existing = $this->pengumumanRepository->getById($id);
        $oldStatus = $existing->status;

        // Handle foto replacement
        if ($foto) {
            // Delete old foto if exists
            if ($existing->foto) {
                $this->deleteWithThumbnail($existing->foto);
            }

            $result = $this->storeWithThumbnail($foto, 'pengumuman/foto', 400, 300);
            $data['foto'] = $result['path'];
        }

        $pengumuman = $this->pengumumanRepository->update($id, $data);

        // Kirim notifikasi jika status berubah menjadi 'aktif'
        $newStatus = $data['status'] ?? $oldStatus;
        if ($newStatus === 'aktif' && $oldStatus !== 'aktif') {
            $this->notifyAllAlumni($pengumuman);
        }

        return $pengumuman;
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

    // ── Notifications ────────────────────────────

    /**
     * Kirim notifikasi pengumuman baru ke semua alumni terverifikasi (via queue)
     */
    private function notifyAllAlumni($pengumuman): void
    {
        $userIds = Alumni::where('status_create', 'ok')
            ->whereNotNull('id_users')
            ->pluck('id_users')
            ->toArray();

        if (empty($userIds)) {
            return;
        }

        // Dispatch ke queue dalam batch per 100 user
        foreach (array_chunk($userIds, 100) as $chunk) {
            SendPengumumanNotifications::dispatch(
                $chunk,
                $pengumuman->id_pengumuman,
                $pengumuman->judul,
            );
        }
    }
}
