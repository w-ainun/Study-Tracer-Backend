<?php

namespace App\Interfaces;

interface LowonganRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 15);
    public function getById(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function getByApprovalStatus(string $status, int $perPage = 15);
    public function updateApprovalStatus(int $id, string $status);
    public function getSavedByUser(int $userId, int $perPage = 15);
    public function toggleSave(int $userId, int $lowonganId): bool;
}
