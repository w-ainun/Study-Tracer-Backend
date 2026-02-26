<?php

namespace App\Interfaces;

interface AdminRepositoryInterface
{
    public function getDashboardStats(): array;
    public function getUserManagementStats(): array;
    public function getLowonganStats(): array;
    public function getTopCompanies(int $limit = 5): array;
    public function getGeographicDistribution(): array;
    public function getPendingAlumni(int $perPage = 15);
    public function approveAlumni(int $alumniId);
    public function rejectAlumni(int $alumniId);
    public function getAllAlumni(array $filters = [], int $perPage = 15);
    public function getAlumniDetail(int $alumniId);
    public function banAlumni(int $alumniId);
    public function deleteUser(int $userId);
}
