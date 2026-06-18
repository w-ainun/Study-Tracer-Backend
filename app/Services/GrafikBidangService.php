<?php

namespace App\Services;

use App\Interfaces\GrafikBidangRepositoryInterface;
use App\Models\Alumni;
use App\Models\Jurusan;
use App\Models\RiwayatStatus;
use Illuminate\Support\Str;

class GrafikBidangService
{
    private GrafikBidangRepositoryInterface $grafikRepository;

    public function __construct(GrafikBidangRepositoryInterface $grafikRepository)
    {
        $this->grafikRepository = $grafikRepository;
    }

    /**
     * Get overall statistics for kesesuaian bidang.
     */
    public function getStats(array $filters = []): array
    {
        return $this->grafikRepository->getKesesuaianStats($filters);
    }

    /**
     * Get breakdown by jurusan.
     */
    public function getByJurusan(array $filters = []): array
    {
        return $this->grafikRepository->getKesesuaianByJurusan($filters);
    }

    /**
     * Get breakdown by tahun lulus.
     */
    public function getByTahunLulus(array $filters = []): array
    {
        return $this->grafikRepository->getKesesuaianByTahunLulus($filters);
    }

    /**
     * Get detailed alumni list.
     */
    public function getDetail(array $filters = [], int $perPage = 15)
    {
        return $this->grafikRepository->getKesesuaianDetail($filters, $perPage);
    }

    /**
     * Compute kesesuaian bidang for a specific riwayat_status.
     * Called when admin approves a career status update.
     *
     * Logic:
     * 1. Get alumni's jurusan → bidang_relevan keywords
     * 2. Get the career detail (posisi pekerjaan / jurusan kuliah / bidang usaha)
     * 3. Keyword matching to determine sesuai/tidak sesuai
     */
    public function computeKesesuaian(RiwayatStatus $riwayat): ?bool
    {
        // Load necessary relationships
        $riwayat->loadMissing([
            'alumni.jurusan',
            'status',
            'pekerjaan.perusahaan',
            'kuliah.jurusanKuliah',
            'wirausaha.bidangUsaha',
        ]);

        $alumni = $riwayat->alumni;
        if (!$alumni || !$alumni->jurusan) {
            return null;
        }

        $jurusan = $alumni->jurusan;
        $keywords = $jurusan->bidang_relevan;

        // If no keywords configured for this jurusan, cannot determine
        if (empty($keywords) || !is_array($keywords)) {
            return null;
        }

        $statusName = $riwayat->status->nama_status ?? '';
        $targetText = '';

        switch ($statusName) {
            case 'Bekerja':
                // Match against posisi (job position) and company name
                $targetText = $riwayat->pekerjaan->posisi ?? '';
                if ($riwayat->pekerjaan && $riwayat->pekerjaan->perusahaan) {
                    $targetText .= ' ' . $riwayat->pekerjaan->perusahaan->nama_perusahaan;
                }
                break;

            case 'Kuliah':
                // Match against jurusan kuliah (college major) and universitas
                if ($riwayat->kuliah && $riwayat->kuliah->jurusanKuliah) {
                    $targetText = $riwayat->kuliah->jurusanKuliah->nama_jurusan ?? '';
                }
                if ($riwayat->kuliah && $riwayat->kuliah->universitas) {
                    $targetText .= ' ' . ($riwayat->kuliah->universitas->nama_universitas ?? '');
                }
                break;

            case 'Wirausaha':
                // Match against bidang usaha and nama usaha
                if ($riwayat->wirausaha && $riwayat->wirausaha->bidangUsaha) {
                    $targetText = $riwayat->wirausaha->bidangUsaha->nama_bidang ?? '';
                }
                $targetText .= ' ' . ($riwayat->wirausaha->nama_usaha ?? '');
                break;

            default:
                return null;
        }

        if (empty(trim($targetText))) {
            return null;
        }

        // Keyword matching (case-insensitive)
        $isSesuai = $this->matchKeywords($keywords, $targetText);

        // Update the riwayat_status record
        $riwayat->update(['is_sesuai_bidang' => $isSesuai]);

        return $isSesuai;
    }

    /**
     * Batch re-compute kesesuaian for all approved riwayat_status.
     * Useful when admin updates bidang_relevan for a jurusan.
     */
    public function recomputeForJurusan(int $jurusanId): int
    {
        $riwayatList = RiwayatStatus::with([
            'alumni.jurusan',
            'status',
            'pekerjaan.perusahaan',
            'kuliah.jurusanKuliah',
            'wirausaha.bidangUsaha',
        ])
        ->whereHas('alumni', function ($q) use ($jurusanId) {
            $q->where('id_jurusan', $jurusanId);
        })
        ->where('approval_status', 'approved')
        ->whereHas('status', function ($q) {
            $q->whereIn('nama_status', ['Bekerja', 'Kuliah', 'Wirausaha']);
        })
        ->get();

        $count = 0;
        foreach ($riwayatList as $riwayat) {
            $this->computeKesesuaian($riwayat);
            $count++;
        }

        return $count;
    }

    /**
     * Match keywords against target text (case-insensitive).
     */
    private function matchKeywords(array $keywords, string $targetText): bool
    {
        $targetLower = Str::lower($targetText);

        foreach ($keywords as $keyword) {
            if (Str::contains($targetLower, Str::lower(trim($keyword)))) {
                return true;
            }
        }

        return false;
    }
}
