<?php

namespace App\Repositories\Alumni;

use App\Interfaces\Alumni\BerandaRepositoryInterface;
use App\Models\Alumni;
use App\Models\Kuesioner;
use App\Models\Lowongan;
use App\Models\Perusahaan;

class BerandaRepository implements BerandaRepositoryInterface
{
    /**
     * Get alumni profile with basic relations for beranda summary.
     */
    public function getAlumniProfile(int $userId)
    {
        return Alumni::with([
            'jurusan',
            'user',
            'skills',
            'riwayatStatus' => fn($q) => $q->latest('id_riwayat')->limit(1),
            'riwayatStatus.status',
            'riwayatStatus.pekerjaan.perusahaan',
            'riwayatStatus.kuliah.universitas',
            'riwayatStatus.kuliah.jurusanKuliah',
            'riwayatStatus.wirausaha.bidangUsaha',
        ])
            ->where('id_users', $userId)
            ->first();
    }

    /**
     * Get recently registered & verified alumni (for jejaring alumni section).
     */
    public function getRecentVerifiedAlumni(int $limit = 8)
    {
        return Alumni::with([
            'jurusan',
            'riwayatStatus' => fn($q) => $q->latest('id_riwayat')->limit(1),
            'riwayatStatus.status',
            'riwayatStatus.pekerjaan.perusahaan',
            'riwayatStatus.kuliah.universitas',
            'riwayatStatus.kuliah.jurusanKuliah',
            'riwayatStatus.wirausaha',
        ])
            ->where('status_create', 'ok')
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get latest published & approved lowongan (for beranda job section).
     */
    public function getLatestPublishedLowongan(int $limit = 6)
    {
        return Lowongan::with(['perusahaan.kota.provinsi', 'skills'])
            ->where('approval_status', 'approved')
            ->where('status', 'open')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top companies ranked by alumni count.
     */
    public function getTopPerusahaan(int $limit = 5)
    {
        return Perusahaan::withCount(['pekerjaan as alumni_count' => function ($query) {
            $query->whereHas('riwayatStatus', function ($q) {
                $q->whereHas('alumni', fn($a) => $a->where('status_create', 'ok'));
            });
        }])
            ->with('kota.provinsi')
            ->having('alumni_count', '>', 0)
            ->orderByDesc('alumni_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get pending kuesioner for alumni based on their status.
     * Returns active kuesioner that the alumni has NOT fully completed yet.
     */
    public function getPendingKuesioner(int $userId)
    {
        return Kuesioner::with('statusKarir')
            ->withCount('pertanyaan')
            ->where('status', 'aktif')
            ->whereNotNull('tanggal_publikasi')
            ->where(function ($query) {
                $query->whereNull('tanggal_mulai')
                    ->orWhere('tanggal_mulai', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('tanggal_selesai')
                    ->orWhere('tanggal_selesai', '>=', now());
            })
            ->orderByDesc('tanggal_publikasi')
            ->get()
            ->filter(function ($kuesioner) use ($userId) {
                if ($kuesioner->pertanyaan_count === 0) return false;

                $answeredCount = $kuesioner->pertanyaan()
                    ->whereHas('jawaban', fn($q) => $q->where('id_user', $userId))
                    ->count();

                return $answeredCount < $kuesioner->pertanyaan_count;
            })
            ->values();
    }

    /**
     * Get status pengajuan timeline data for alumni.
     */
    public function getStatusPengajuan(int $userId)
    {
        return Alumni::with('user')
            ->where('id_users', $userId)
            ->first();
    }
}
