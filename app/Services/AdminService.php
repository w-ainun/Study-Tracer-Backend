<?php

namespace App\Services;

use App\Interfaces\AdminRepositoryInterface;

class AdminService
{
    private AdminRepositoryInterface $adminRepository;

    public function __construct(AdminRepositoryInterface $adminRepository)
    {
        $this->adminRepository = $adminRepository;
    }

    public function getDashboardStats(): array
    {
        return $this->adminRepository->getDashboardStats();
    }

    public function getPendingAlumni(int $perPage = 15)
    {
        return $this->adminRepository->getPendingAlumni($perPage);
    }

    public function approveAlumni(int $alumniId)
    {
        return $this->adminRepository->approveAlumni($alumniId);
    }

    public function rejectAlumni(int $alumniId)
    {
        return $this->adminRepository->rejectAlumni($alumniId);
    }

    public function getAllAlumni(array $filters = [], int $perPage = 15)
    {
        return $this->adminRepository->getAllAlumni($filters, $perPage);
    }

    public function getAlumniDetail(int $alumniId)
    {
        return $this->adminRepository->getAlumniDetail($alumniId);
    }

    public function deleteUser(int $userId)
    {
        return $this->adminRepository->deleteUser($userId);
    }

    public function getLowonganStats(): array
    {
        return $this->adminRepository->getLowonganStats();
    }

    public function getTopCompanies(int $limit = 5): array
    {
        return $this->adminRepository->getTopCompanies($limit);
    }

    public function getGeographicDistribution(): array
    {
        return $this->adminRepository->getGeographicDistribution();
    }
}
