<?php

namespace App\Services\Alumni;

use App\Interfaces\Alumni\BerandaRepositoryInterface;

class BerandaService
{
    private BerandaRepositoryInterface $berandaRepository;

    public function __construct(BerandaRepositoryInterface $berandaRepository)
    {
        $this->berandaRepository = $berandaRepository;
    }

    /**
     * Get full beranda/dashboard data for alumni.
     * Sections restricted by verification status:
     * - Always available: profile, status_pengajuan, kuesioner_pending
     * - Verified only: alumni_terbaru, lowongan_terbaru, top_perusahaan
     */
    public function getBerandaData(int $userId): array
    {
        $alumni = $this->berandaRepository->getAlumniProfile($userId);

        if (!$alumni) {
            throw new \Exception('Profil alumni tidak ditemukan.');
        }

        $isVerified = $alumni->status_create === 'ok';

        $data = [
            'profile' => $alumni,
            'is_verified' => $isVerified,
            'status_pengajuan' => $this->buildStatusPengajuan($alumni),
            'kuesioner_pending' => $this->berandaRepository->getPendingKuesioner($userId),
        ];

        // Restricted sections — only available when alumni is verified
        if ($isVerified) {
            $data['alumni_terbaru'] = $this->berandaRepository->getRecentVerifiedAlumni(8);
            $data['lowongan_terbaru'] = $this->berandaRepository->getLatestPublishedLowongan(6);
            $data['top_perusahaan'] = $this->berandaRepository->getTopPerusahaan(5);
        } else {
            $data['alumni_terbaru'] = [];
            $data['lowongan_terbaru'] = [];
            $data['top_perusahaan'] = [];
        }

        return $data;
    }

    /**
     * Get status pengajuan (verification timeline) for alumni.
     */
    public function getStatusPengajuan(int $userId): array
    {
        $alumni = $this->berandaRepository->getStatusPengajuan($userId);

        if (!$alumni) {
            throw new \Exception('Profil alumni tidak ditemukan.');
        }

        return $this->buildStatusPengajuan($alumni);
    }

    /**
     * Build status pengajuan timeline based on alumni status.
     * Steps: Pendaftaran Dikirim → Verifikasi Berlangsung → Persetujuan Akhir
     */
    private function buildStatusPengajuan($alumni): array
    {
        $status = $alumni->status_create;
        $createdAt = $alumni->created_at;
        $updatedAt = $alumni->updated_at;

        // Step definitions
        $steps = [];

        // Step 1: Pendaftaran Dikirim — always completed once registered
        $steps[] = [
            'title' => 'Pendaftaran telah Dikirim',
            'status' => 'completed',
            'date' => $createdAt?->format('d F Y • H:i'),
            'description' => 'Detail akun dan dokumen verifikasi alumni Anda telah Diterima',
        ];

        // Step 2: Verifikasi Sedang Berlangsung
        if ($status === 'pending') {
            $steps[] = [
                'title' => 'Verifikasi Sedang Berlangsung',
                'status' => 'current',
                'date' => null,
                'description' => 'Tim admin kami sedang memvalidasi tahun kelulusan dan Nomor Induk Mahasiswa Anda dengan data universitas.',
            ];
        } elseif ($status === 'rejected') {
            $steps[] = [
                'title' => 'Verifikasi Ditolak',
                'status' => 'rejected',
                'date' => $updatedAt?->format('d F Y • H:i'),
                'description' => 'Pengajuan verifikasi Anda telah ditolak. Silakan perbarui data dan ajukan kembali.',
            ];
        } else {
            // ok or banned — verification was completed
            $steps[] = [
                'title' => 'Verifikasi Sedang Berlangsung',
                'status' => 'completed',
                'date' => $updatedAt?->format('d F Y • H:i'),
                'description' => 'Tim admin telah selesai memvalidasi data Anda.',
            ];
        }

        // Step 3: Persetujuan Akhir
        if ($status === 'ok') {
            $steps[] = [
                'title' => 'Persetujuan Akhir',
                'status' => 'completed',
                'date' => $updatedAt?->format('d F Y • H:i'),
                'description' => 'Akun Anda telah diverifikasi dan aktif. Anda dapat mengakses seluruh fitur.',
            ];
        } elseif ($status === 'rejected') {
            $steps[] = [
                'title' => 'Persetujuan Akhir',
                'status' => 'rejected',
                'date' => null,
                'description' => 'Verifikasi gagal. Silakan perbaiki data Anda.',
            ];
        } elseif ($status === 'banned') {
            $steps[] = [
                'title' => 'Persetujuan Akhir',
                'status' => 'banned',
                'date' => $updatedAt?->format('d F Y • H:i'),
                'description' => 'Akun Anda telah diblokir oleh admin.',
            ];
        } else {
            $steps[] = [
                'title' => 'Persetujuan Akhir',
                'status' => 'pending',
                'date' => null,
                'description' => 'Menunggu Penyelesaian verifikasi',
            ];
        }

        return [
            'status' => $status,
            'estimasi' => '2-3 Hari Kerja',
            'steps' => $steps,
        ];
    }
}
