<?php

namespace App\Interfaces;

interface AdminRepositoryInterface
{
    public function getDashboardStats(): array;
    public function getPendingAlumni(int $perPage = 15);
    public function approveAlumni(int $alumniId);
    public function rejectAlumni(int $alumniId);
    public function getAllAlumni(array $filters = [], int $perPage = 15);
    public function getAlumniDetail(int $alumniId);
    public function deleteUser(int $userId);
}
