<?php

namespace App\Services;

use App\Interfaces\LowonganRepositoryInterface;

class LowonganService
{
    private LowonganRepositoryInterface $lowonganRepository;

    public function __construct(LowonganRepositoryInterface $lowonganRepository)
    {
        $this->lowonganRepository = $lowonganRepository;
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

        $lowongan = $this->lowonganRepository->update($id, $data);

        if ($skillIds !== null) {
            $this->lowonganRepository->syncSkills($id, $skillIds);
            $lowongan->load('skills');
        }

        return $lowongan;
    }

    public function delete(int $id)
    {
        return $this->lowonganRepository->delete($id);
    }

    public function getPending(int $perPage = 15)
    {
        return $this->lowonganRepository->getByApprovalStatus('pending', $perPage);
    }

    public function approve(int $id)
    {
        // When approved, also set status to published (active)
        return $this->lowonganRepository->update($id, [
            'approval_status' => 'approved',
            'status' => 'published'
        ]);
    }

    public function reject(int $id)
    {
        return $this->lowonganRepository->updateApprovalStatus($id, 'rejected');
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
