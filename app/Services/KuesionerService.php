<?php

namespace App\Services;

use App\Events\AccessLockChanged;
use App\Events\DashboardStatsUpdated;
use App\Events\KuesionerUpdated;
use App\Interfaces\KuesionerRepositoryInterface;
use App\Jobs\SendBulkNotifications;
use App\Models\Alumni;

class KuesionerService
{
    private KuesionerRepositoryInterface $kuesionerRepository;
    private NotificationService $notificationService;

    public function __construct(
        KuesionerRepositoryInterface $kuesionerRepository,
        NotificationService $notificationService
    ) {
        $this->kuesionerRepository = $kuesionerRepository;
        $this->notificationService = $notificationService;
    }

    public function getAll(array $filters = [], int $perPage = 15)
    {
        return $this->kuesionerRepository->getAll($filters, $perPage);
    }

    public function getById(int $id)
    {
        return $this->kuesionerRepository->getById($id);
    }

    public function create(array $data)
    {
        $kuesioner = $this->kuesionerRepository->create($data);

        // Jika kuesioner langsung dibuat dengan status 'aktif', clear cache & notify
        if (($data['status'] ?? null) === 'aktif' && $kuesioner) {
            $this->clearKuesionerCache($kuesioner);
            $this->notifyRelevantAlumni($kuesioner);
        }

        return $kuesioner;
    }

    public function update(int $id, array $data)
    {
        // Cek status lama sebelum update
        $existing = $this->kuesionerRepository->getById($id);
        $oldStatus = $existing->status;

        $kuesioner = $this->kuesionerRepository->update($id, $data);

        // Jika status berubah menjadi 'aktif', clear cache & notify
        $newStatus = $data['status'] ?? $oldStatus;
        if ($newStatus === 'aktif' && $oldStatus !== 'aktif' && $kuesioner) {
            $this->clearKuesionerCache($kuesioner);
            $this->notifyRelevantAlumni($kuesioner);
        }

        return $kuesioner;
    }

    public function delete(int $id)
    {
        return $this->kuesionerRepository->delete($id);
    }

    public function getAllPertanyaan(array $filters = [], int $perPage = 15)
    {
        return $this->kuesionerRepository->getAllPertanyaan($filters, $perPage);
    }

    public function addPertanyaan(int $kuesionerId, array $data)
    {
        return $this->kuesionerRepository->addPertanyaan($kuesionerId, $data);
    }

    public function updatePertanyaan(int $pertanyaanId, array $data)
    {
        return $this->kuesionerRepository->updatePertanyaan($pertanyaanId, $data);
    }

    public function deletePertanyaan(int $pertanyaanId)
    {
        return $this->kuesionerRepository->deletePertanyaan($pertanyaanId);
    }

    public function submitJawaban(int $userId, array $jawabanData)
    {
        $result = $this->kuesionerRepository->submitJawaban($userId, $jawabanData);
        
        // Clear cache setelah submit jawaban kuesioner
        \Illuminate\Support\Facades\Cache::forget("user:{$userId}:kuesioner_completed");
        \Illuminate\Support\Facades\Cache::forget("user:{$userId}:can_access_all");

        // Broadcast access lock change — recalculate via AuthService
        $authService = app(AuthService::class);
        $canAccessAll = $authService->calculateCanAccessAll($userId);
        broadcast(new AccessLockChanged($userId, $canAccessAll))->toOthers();

        // Notify admin dashboard of new kuesioner response
        broadcast(new DashboardStatsUpdated('kuesioner_response', [
            'user_id' => $userId,
            'kuesioner_id' => $jawabanData['kuesioner_id'] ?? null,
        ]))->toOthers();
        
        return $result;
    }

    public function getPublished(int $perPage = 15)
    {
        return $this->kuesionerRepository->getPublished($perPage);
    }

    public function getAllPublished(array $filters = [], int $perPage = 15)
    {
        return $this->kuesionerRepository->getAllPublished($filters, $perPage);
    }

    public function getPublishedByStatus(int $statusId)
    {
        return $this->kuesionerRepository->getPublishedByStatus($statusId);
    }

    public function getWithPertanyaan(int $kuesionerId)
    {
        return $this->kuesionerRepository->getKuesionerWithPertanyaan($kuesionerId);
    }

    public function getAlumniJawaban(int $kuesionerId, array $filters = [])
    {
        return $this->kuesionerRepository->getAlumniJawaban($kuesionerId, $filters);
    }

    public function getAlumniJawabanDetail(int $kuesionerId, int $alumniId)
    {
        return $this->kuesionerRepository->getAlumniJawabanDetail($kuesionerId, $alumniId);
    }

    public function updateKuesionerStatus(int $kuesionerId, string $status)
    {
        $kuesioner = $this->kuesionerRepository->updateKuesionerStatus($kuesionerId, $status);
        
        // Clear cache kuesioner untuk semua alumni yang relevan
        $this->clearKuesionerCache($kuesioner);

        // Trigger notifikasi ke alumni yang sesuai jika status berubah menjadi 'aktif'
        if ($status === 'aktif' && $kuesioner) {
            $this->notifyRelevantAlumni($kuesioner);
        }
        
        return $kuesioner;
    }

    /**
     * Clear cache kuesioner_completed dan can_access_all untuk alumni yang relevan
     */
    private function clearKuesionerCache($kuesioner, string $action = 'activated')
    {
        $statusId = $kuesioner->id_status;
        
        if ($statusId) {
            // Clear cache untuk alumni dengan status karir yang sesuai
            $userIds = Alumni::whereHas('riwayatStatus', function ($query) use ($statusId) {
                $query->where('id_status', $statusId)
                    ->where('approval_status', 'approved');
            })->whereNotNull('id_users')->pluck('id_users')->toArray();
        } else {
            // Clear cache untuk semua alumni
            $userIds = Alumni::where('status_create', 'ok')
                ->whereNotNull('id_users')
                ->pluck('id_users')
                ->toArray();
        }

        foreach ($userIds as $userId) {
            \Illuminate\Support\Facades\Cache::forget("user:{$userId}:kuesioner_completed");
            \Illuminate\Support\Facades\Cache::forget("user:{$userId}:can_access_all");

            // Broadcast kuesioner update & access lock change to each affected user
            broadcast(new KuesionerUpdated(
                $userId,
                $kuesioner->id_kuesioner,
                $kuesioner->title,
                $action,
            ))->toOthers();

            broadcast(new AccessLockChanged($userId, false))->toOthers();
        }
    }

    /**
     * Notifikasi alumni yang sesuai dengan kuesioner baru (via queue)
     */
    private function notifyRelevantAlumni($kuesioner)
    {
        $statusId = $kuesioner->id_status;
        $kuesionerTitle = $kuesioner->title;
        
        if ($statusId) {
            $statusName = $kuesioner->statusKarir->nama_status ?? 'status tertentu';
            
            // Ambil user IDs saja (efisien, tanpa load full model)
            $userIds = Alumni::whereHas('riwayatStatus', function ($query) use ($statusId) {
                $query->where('id_status', $statusId)
                    ->where('approval_status', 'approved');
            })->whereNotNull('id_users')->pluck('id_users')->toArray();
        } else {
            $statusName = 'semua alumni';
            $userIds = Alumni::where('status_create', 'ok')
                ->whereNotNull('id_users')
                ->pluck('id_users')
                ->toArray();
        }

        if (empty($userIds)) {
            return;
        }

        // Dispatch ke queue dalam batch per 100 user
        foreach (array_chunk($userIds, 100) as $chunk) {
            SendBulkNotifications::dispatch(
                $chunk,
                $kuesioner->id_kuesioner,
                $kuesionerTitle,
                $statusName,
            );
        }
    }

    public function getStatistics(int $kuesionerId)
    {
        return $this->kuesionerRepository->getStatistics($kuesionerId);
    }

    /**
     * Get riwayat kuesioner yang sudah dijawab oleh user (alumni).
     */
    public function getRiwayatKuesioner(int $userId)
    {
        return $this->kuesionerRepository->getRiwayatKuesioner($userId);
    }

}
