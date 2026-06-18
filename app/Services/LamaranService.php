<?php

namespace App\Services;

use App\Interfaces\LamaranRepositoryInterface;
use App\Jobs\SendNotificationJob;
use App\Models\Lamaran;
use App\Models\Lowongan;
use Illuminate\Support\Facades\DB;

class LamaranService
{
    private LamaranRepositoryInterface $lamaranRepository;
    private NotificationService $notificationService;
    private JobMatchingService $jobMatchingService;

    public function __construct(
        LamaranRepositoryInterface $lamaranRepository,
        NotificationService $notificationService,
        JobMatchingService $jobMatchingService
    ) {
        $this->lamaranRepository = $lamaranRepository;
        $this->notificationService = $notificationService;
        $this->jobMatchingService = $jobMatchingService;
    }

    /**
     * Alumni applies to a lowongan.
     * Validates: not duplicate, lowongan is published.
     */
    public function apply(int $alumniId, int $lowonganId, ?string $catatan = null)
    {
        // Check if lowongan exists and is published
        $lowongan = Lowongan::where('id_lowongan', $lowonganId)
            ->where('status', 'published')
            ->where('approval_status', 'approved')
            ->first();

        if (!$lowongan) {
            throw new \Exception('Lowongan tidak ditemukan atau tidak tersedia.');
        }

        // Check for duplicate application
        $existing = Lamaran::where('id_alumni', $alumniId)
            ->where('id_lowongan', $lowonganId)
            ->first();

        if ($existing) {
            throw new \Exception('Anda sudah melamar ke lowongan ini.');
        }

        $lamaran = $this->lamaranRepository->apply($alumniId, $lowonganId, $catatan);

        // Notify the lowongan poster (if alumni-posted)
        if ($lowongan->id_users) {
            $alumni = $lamaran->alumni;
            $this->notificationService->create(
                $lowongan->id_users,
                'lamaran',
                'Lamaran Baru',
                "Ada lamaran baru dari {$alumni->nama_alumni} untuk lowongan \"{$lowongan->judul_lowongan}\".",
                [
                    'lamaran_id' => $lamaran->id_lamaran,
                    'lowongan_id' => $lowonganId,
                    'alumni_id' => $alumniId,
                    'alumni_name' => $alumni->nama_alumni,
                ]
            );
        }

        return $lamaran->load(['lowongan.perusahaan', 'lowongan.pekerjaan']);
    }

    /**
     * Get alumni's application history.
     */
    public function getAlumniHistory(int $alumniId, array $filters = [], int $perPage = 15)
    {
        return $this->lamaranRepository->getByAlumni($alumniId, $filters, $perPage);
    }

    /**
     * Get alumni's application stats.
     */
    public function getAlumniStats(int $alumniId): array
    {
        return $this->lamaranRepository->getAlumniStats($alumniId);
    }

    /**
     * Cancel a pending application.
     */
    public function cancel(int $lamaranId, int $alumniId): bool
    {
        $lamaran = $this->lamaranRepository->findById($lamaranId);

        if ($lamaran->id_alumni !== $alumniId) {
            throw new \Exception('Anda tidak memiliki akses ke lamaran ini.');
        }

        if ($lamaran->status !== 'pending') {
            throw new \Exception('Hanya lamaran dengan status pending yang bisa dibatalkan.');
        }

        return $this->lamaranRepository->delete($lamaranId);
    }

    // =====================
    // ADMIN OPERATIONS
    // =====================

    /**
     * Admin accepts a lamaran.
     */
    public function terima(int $lamaranId, ?string $catatanAdmin = null)
    {
        $lamaran = $this->lamaranRepository->findById($lamaranId);

        if ($lamaran->status !== 'pending') {
            throw new \Exception('Lamaran ini sudah diproses.');
        }

        $result = $this->lamaranRepository->updateStatus($lamaranId, 'diterima', $catatanAdmin);

        // Notify the alumni
        if ($result->alumni && $result->alumni->id_users) {
            $jobTitle = $result->lowongan->judul_lowongan ?? 'Lowongan';
            $this->notificationService->create(
                $result->alumni->id_users,
                'lamaran',
                'Lamaran Diterima 🎉',
                "Selamat! Lamaran Anda untuk \"{$jobTitle}\" telah diterima. Periksa detail dan informasi selanjutnya.",
                [
                    'lamaran_id' => $lamaranId,
                    'lowongan_id' => $result->id_lowongan,
                    'status' => 'diterima',
                ]
            );
        }

        return $result;
    }

    /**
     * Admin rejects a lamaran.
     * Triggers job recommendation notifications for the rejected alumni.
     */
    public function tolak(int $lamaranId, ?string $catatanAdmin = null)
    {
        $lamaran = $this->lamaranRepository->findById($lamaranId);

        if ($lamaran->status !== 'pending') {
            throw new \Exception('Lamaran ini sudah diproses.');
        }

        $result = $this->lamaranRepository->updateStatus($lamaranId, 'ditolak', $catatanAdmin);

        // Notify the alumni about rejection
        if ($result->alumni && $result->alumni->id_users) {
            $jobTitle = $result->lowongan->judul_lowongan ?? 'Lowongan';
            $this->notificationService->create(
                $result->alumni->id_users,
                'lamaran',
                'Lamaran Ditolak',
                "Mohon maaf, lamaran Anda untuk \"{$jobTitle}\" tidak diterima." .
                ($catatanAdmin ? " Catatan: {$catatanAdmin}" : '') .
                " Jangan menyerah! Kami akan mencarikan lowongan lain yang sesuai.",
                [
                    'lamaran_id' => $lamaranId,
                    'lowongan_id' => $result->id_lowongan,
                    'status' => 'ditolak',
                    'catatan' => $catatanAdmin,
                ]
            );

            // Send new job recommendations after rejection
            $this->jobMatchingService->sendJobNotifications(
                $result->alumni->id_users,
                $result->id_alumni
            );
        }

        return $result;
    }

    /**
     * Get all lamaran (admin).
     */
    public function getAll(array $filters = [], int $perPage = 15)
    {
        return $this->lamaranRepository->getAll($filters, $perPage);
    }

    /**
     * Get lamaran by lowongan (admin).
     */
    public function getByLowongan(int $lowonganId, array $filters = [], int $perPage = 15)
    {
        return $this->lamaranRepository->getByLowongan($lowonganId, $filters, $perPage);
    }

    /**
     * Get global stats (admin).
     */
    public function getGlobalStats(): array
    {
        return $this->lamaranRepository->getGlobalStats();
    }
}
