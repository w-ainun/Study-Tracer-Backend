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
        return $this->lowonganRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        return $this->lowonganRepository->update($id, $data);
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
}
