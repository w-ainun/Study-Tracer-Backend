<?php

namespace App\Services;

use App\Interfaces\KuesionerRepositoryInterface;
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
        return $this->kuesionerRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        return $this->kuesionerRepository->update($id, $data);
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
        return $this->kuesionerRepository->submitJawaban($userId, $jawabanData);
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
        
        // Trigger notifikasi ke alumni yang sesuai jika status berubah menjadi 'aktif'
        if ($status === 'aktif' && $kuesioner) {
            $this->notifyRelevantAlumni($kuesioner);
        }
        
        return $kuesioner;
    }

    /**
     * Notifikasi alumni yang sesuai dengan kuesioner baru
     */
    private function notifyRelevantAlumni($kuesioner)
    {
        $statusId = $kuesioner->id_status;
        $kuesionerTitle = $kuesioner->judul;
        
        // Jika kuesioner untuk status tertentu
        if ($statusId) {
            // Cari semua alumni dengan status karir tersebut
            $alumni = Alumni::whereHas('riwayatStatus', function ($query) use ($statusId) {
                $query->where('id_status', $statusId)
                    ->where('approval_status', 'approved');
            })->with('user')->get();
            
            $statusName = $kuesioner->status->nama_status ?? 'status tertentu';
            
            foreach ($alumni as $alum) {
                if ($alum->id_users) {
                    $this->notificationService->notifyNewKuesioner(
                        $alum->id_users,
                        $kuesioner->id_kuesioner,
                        $kuesionerTitle,
                        $statusName
                    );
                }
            }
        } else {
            // Jika kuesioner untuk semua alumni
            $allAlumni = Alumni::with('user')->where('status_create', 'ok')->get();
            
            foreach ($allAlumni as $alum) {
                if ($alum->id_users) {
                    $this->notificationService->notifyNewKuesioner(
                        $alum->id_users,
                        $kuesioner->id_kuesioner,
                        $kuesionerTitle,
                        'semua alumni'
                    );
                }
            }
        }
    }

    public function getStatistics(int $kuesionerId)
    {
        return $this->kuesionerRepository->getStatistics($kuesionerId);
    }

}
