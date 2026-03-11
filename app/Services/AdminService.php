<?php

namespace App\Services;

use App\Interfaces\AdminRepositoryInterface;
use App\Traits\GeneratesThumbnail;

class AdminService
{
    use GeneratesThumbnail;
    private AdminRepositoryInterface $adminRepository;
    private NotificationService $notificationService;

    public function __construct(
        AdminRepositoryInterface $adminRepository,
        NotificationService $notificationService
    ) {
        $this->adminRepository = $adminRepository;
        $this->notificationService = $notificationService;
    }

    public function getDashboardStats(): array
    {
        return $this->adminRepository->getDashboardStats();
    }

    public function getUserManagementStats(): array
    {
        return $this->adminRepository->getUserManagementStats();
    }

    public function getPendingAlumni(int $perPage = 15)
    {
        return $this->adminRepository->getPendingAlumni($perPage);
    }

    public function approveAlumni(int $alumniId)
    {
        $alumni = $this->adminRepository->approveAlumni($alumniId);
        
        // Trigger notifikasi
        if ($alumni && $alumni->id_users) {
            $this->notificationService->notifyAccountVerified($alumni->id_users);
            
            // Clear cache setelah approve alumni
            \Illuminate\Support\Facades\Cache::forget("user:{$alumni->id_users}:can_access_all");
            \Illuminate\Support\Facades\Cache::forget("user:{$alumni->id_users}:kuesioner_completed");
        }
        
        return $alumni;
    }

    public function rejectAlumni(int $alumniId)
    {
        $alumni = $this->adminRepository->rejectAlumni($alumniId);
        
        // Trigger notifikasi
        if ($alumni && $alumni->id_users) {
            $this->notificationService->notifyAccountRejected($alumni->id_users);
            
            // Clear cache setelah reject alumni
            \Illuminate\Support\Facades\Cache::forget("user:{$alumni->id_users}:can_access_all");
            \Illuminate\Support\Facades\Cache::forget("user:{$alumni->id_users}:kuesioner_completed");
        }
        
        return $alumni;
    }

    public function banAlumni(int $alumniId)
    {
        $alumni = $this->adminRepository->banAlumni($alumniId);
        
        // Trigger notifikasi
        if ($alumni && $alumni->id_users) {
            $this->notificationService->notifyAccountBanned($alumni->id_users);
        }
        
        return $alumni;
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
        // Get user data before deleting to clean up files
        $user = \App\Models\User::with('alumni')->find($userId);
        
        // Delete alumni foto and thumbnail if exists
        if ($user && $user->alumni && $user->alumni->foto) {
            $this->deleteWithThumbnail($user->alumni->foto);
        }
        
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

    // ── Pending Career Status ────────────────────────

    public function getPendingCareerUpdates()
    {
        return $this->adminRepository->getPendingCareerUpdates();
    }

    public function approveCareerUpdate(int $riwayatId)
    {
        $riwayat = $this->adminRepository->approveCareerUpdate($riwayatId);
        
        // Trigger notifikasi
        if ($riwayat && $riwayat->alumni && $riwayat->alumni->id_users) {
            $statusName = $riwayat->status->nama_status ?? 'Status Karir';
            $this->notificationService->notifyCareerStatusApproved(
                $riwayat->alumni->id_users,
                $riwayatId,
                $statusName
            );
        }
        
        return $riwayat;
    }

    public function rejectCareerUpdate(int $riwayatId)
    {
        // Get data sebelum delete (untuk notifikasi)
        $riwayat = \App\Models\RiwayatStatus::with(['alumni', 'status'])->findOrFail($riwayatId);
        $userId = $riwayat->alumni->id_users ?? null;
        $statusName = $riwayat->status->nama_status ?? 'Status Karir';
        
        $result = $this->adminRepository->rejectCareerUpdate($riwayatId);
        
        // Trigger notifikasi
        if ($userId) {
            $this->notificationService->notifyCareerStatusRejected(
                $userId,
                $riwayatId,
                $statusName
            );
        }
        
        return $result;
    }
}
