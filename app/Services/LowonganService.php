<?php

namespace App\Services;

use App\Interfaces\LowonganRepositoryInterface;
use App\Jobs\SendNotificationJob;
use App\Traits\GeneratesThumbnail;

class LowonganService
{
    use GeneratesThumbnail;
    private LowonganRepositoryInterface $lowonganRepository;
    private NotificationService $notificationService;

    public function __construct(
        LowonganRepositoryInterface $lowonganRepository,
        NotificationService $notificationService
    ) {
        $this->lowonganRepository = $lowonganRepository;
        $this->notificationService = $notificationService;
    }

    public function getAll(array $filters = [], int $perPage = 15)
    {
        return $this->lowonganRepository->getAll($filters, $perPage);
    }

    public function getById(int $id)
    {
        return $this->lowonganRepository->getById($id);
    }

    public function create(array $data)
    {
        $skillIds = $data['skills'] ?? [];
        unset($data['skills']);

        $lowongan = $this->lowonganRepository->create($data);

        if (!empty($skillIds)) {
            $this->lowonganRepository->syncSkills($lowongan->id_lowongan, $skillIds);
            $lowongan->load('skills');
        }

        return $lowongan;
    }

    public function update(int $id, array $data)
    {
        $skillIds = $data['skills'] ?? null;
        unset($data['skills']);

        // If updating foto_lowongan, delete old one first
        if (isset($data['foto_lowongan'])) {
            $lowongan = $this->lowonganRepository->getById($id);
            if ($lowongan && $lowongan->foto_lowongan) {
                $this->deleteWithThumbnail($lowongan->foto_lowongan);
            }
        }

        $lowongan = $this->lowonganRepository->update($id, $data);

        if ($skillIds !== null) {
            $this->lowonganRepository->syncSkills($id, $skillIds);
            $lowongan->load('skills');
        }

        return $lowongan;
    }

    public function delete(int $id)
    {
        // Get lowongan data before deleting to clean up files
        $lowongan = $this->lowonganRepository->getById($id);
        
        // Delete foto_lowongan and thumbnail if exists
        if ($lowongan && $lowongan->foto_lowongan) {
            $this->deleteWithThumbnail($lowongan->foto_lowongan);
        }
        
        return $this->lowonganRepository->delete($id);
    }

    public function getPending(int $perPage = 15)
    {
        return $this->lowonganRepository->getByApprovalStatus('pending', $perPage);
    }

    public function approve(int $id)
    {
        // Get lowongan data before updating (untuk notifikasi)
        $lowongan = $this->lowonganRepository->getById($id);
        
        // When approved, set status to published (active) + record timestamp
        $updatedLowongan = $this->lowonganRepository->update($id, [
            'approval_status' => 'approved',
            'status' => 'published',
            'approved_at' => now(),
            'rejected_at' => null, // clear any previous rejection
        ]);
        
        // Dispatch notifikasi ke queue (non-blocking)
        if ($lowongan && $lowongan->id_users) {
            SendNotificationJob::dispatch(
                $lowongan->id_users,
                'notifyLowonganApproved',
                [$id, $lowongan->judul_lowongan]
            );
        }
        
        return $updatedLowongan;
    }

    public function reject(int $id)
    {
        // Get lowongan data before updating (untuk notifikasi)
        $lowongan = $this->lowonganRepository->getById($id);
        
        $updatedLowongan = $this->lowonganRepository->update($id, [
            'approval_status' => 'rejected',
            'rejected_at' => now(),
        ]);
        
        // Dispatch notifikasi ke queue (non-blocking)
        if ($lowongan && $lowongan->id_users) {
            SendNotificationJob::dispatch(
                $lowongan->id_users,
                'notifyLowonganRejected',
                [$id, $lowongan->judul_lowongan]
            );
        }
        
        return $updatedLowongan;
    }

    public function repost(int $id)
    {
        return $this->lowonganRepository->update($id, [
            'status' => 'published',
            'approval_status' => 'approved',
            'lowongan_selesai' => null,
        ]);
    }

    public function getApproved(array $filters = [], int $perPage = 15)
    {
        $filters['approval_status'] = 'approved';
        $filters['status'] = 'published';
        return $this->lowonganRepository->getAll($filters, $perPage);
    }

    public function getSavedByUser(int $userId, int $perPage = 15)
    {
        return $this->lowonganRepository->getSavedByUser($userId, $perPage);
    }

    public function toggleSave(int $userId, int $lowonganId): bool
    {
        return $this->lowonganRepository->toggleSave($userId, $lowonganId);
    }

    public function getPublishedForAlumni(array $alumniSkillIds, array $filters = [], int $perPage = 15)
    {
        return $this->lowonganRepository->getPublishedSortedBySkillMatch($alumniSkillIds, $filters, $perPage);
    }

    /**
     * Update status of a lowongan
     */
    public function updateStatus(int $id, string $status)
    {
        $validStatuses = ['draft', 'published', 'closed'];
        
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException('Status tidak valid. Gunakan: draft, published, atau closed');
        }

        return $this->lowonganRepository->update($id, ['status' => $status]);
    }

    /**
     * Auto-close all expired lowongan (where lowongan_selesai < today)
     * Returns count of closed lowongan
     */
    public function autoCloseExpired(): int
    {
        return $this->lowonganRepository->closeExpiredLowongan();
    }
}
