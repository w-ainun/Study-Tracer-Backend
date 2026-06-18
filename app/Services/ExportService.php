<?php

namespace App\Services;

use App\Models\Alumni;
use App\Models\Lamaran;
use App\Models\Lowongan;
use App\Models\RiwayatStatus;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /**
     * Export all alumni data with career status and kesesuaian bidang (CSV streamed).
     */
    public function exportAlumniComplete(array $filters = []): StreamedResponse
    {
        $headers = $this->csvHeaders('alumni_lengkap');

        $callback = function () use ($filters) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // BOM for Excel

            fputcsv($handle, [
                'ID', 'Nama', 'NIS', 'NISN', 'Jenis Kelamin',
                'Tanggal Lahir', 'Tempat Lahir', 'Tahun Masuk', 'Tahun Lulus',
                'Alamat', 'No HP', 'Jurusan', 'Status Akun', 'Email',
                'Status Karier', 'Detail Karier', 'Sesuai Bidang',
                'Total Lamaran', 'Lamaran Diterima', 'Lamaran Ditolak',
                'Dibuat',
            ]);

            $query = Alumni::with([
                'user', 'jurusan',
                'riwayatStatus' => function ($q) {
                    $q->where('approval_status', 'approved')
                      ->with(['status', 'pekerjaan.perusahaan', 'kuliah.universitas', 'kuliah.jurusanKuliah', 'wirausaha.bidangUsaha'])
                      ->orderByDesc('id_riwayat');
                },
            ]);

            $this->applyAlumniFilters($query, $filters);

            $query->orderBy('created_at', 'desc')
                ->chunk(500, function ($alumni) use ($handle) {
                    foreach ($alumni as $item) {
                        $latestRiwayat = $item->riwayatStatus->first();
                        $statusKarier = $latestRiwayat?->status?->nama_status ?? '-';
                        $detailKarier = $this->getCareerDetail($latestRiwayat);
                        $sesuaiBidang = $latestRiwayat?->is_sesuai_bidang === null
                            ? 'Belum Ditentukan'
                            : ($latestRiwayat->is_sesuai_bidang ? 'Sesuai' : 'Tidak Sesuai');

                        // Count lamaran
                        $lamaranStats = Lamaran::where('id_alumni', $item->id_alumni)
                            ->selectRaw("
                                COUNT(*) as total,
                                SUM(CASE WHEN status = 'diterima' THEN 1 ELSE 0 END) as diterima,
                                SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak
                            ")->first();

                        fputcsv($handle, [
                            $item->id_alumni,
                            $item->nama_alumni,
                            $item->nis,
                            $item->nisn,
                            $item->jenis_kelamin,
                            $item->tanggal_lahir?->format('Y-m-d'),
                            $item->tempat_lahir,
                            $item->tahun_masuk,
                            $item->tahun_lulus?->format('Y-m-d'),
                            $item->alamat,
                            $item->no_hp,
                            $item->jurusan?->nama_jurusan ?? '-',
                            $item->status_create,
                            $item->user?->email_users ?? '-',
                            $statusKarier,
                            $detailKarier,
                            $sesuaiBidang,
                            (int) ($lamaranStats->total ?? 0),
                            (int) ($lamaranStats->diterima ?? 0),
                            (int) ($lamaranStats->ditolak ?? 0),
                            $item->created_at?->format('Y-m-d H:i:s'),
                        ]);
                    }
                });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export lamaran data (CSV streamed).
     */
    public function exportLamaran(array $filters = []): StreamedResponse
    {
        $headers = $this->csvHeaders('lamaran');

        $callback = function () use ($filters) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID Lamaran', 'Nama Alumni', 'NIS', 'Jurusan',
                'Judul Lowongan', 'Perusahaan', 'Status Lamaran',
                'Tanggal Apply', 'Tanggal Respon', 'Catatan Alumni',
                'Catatan Admin',
            ]);

            $query = Lamaran::with([
                'alumni.jurusan',
                'lowongan.perusahaan',
            ]);

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->whereHas('alumni', fn($sq) => $sq->where('nama_alumni', 'like', "%{$search}%"))
                      ->orWhereHas('lowongan', fn($sq) => $sq->where('judul_lowongan', 'like', "%{$search}%"));
                });
            }

            $query->orderByDesc('tanggal_apply')
                ->chunk(500, function ($lamarans) use ($handle) {
                    foreach ($lamarans as $item) {
                        fputcsv($handle, [
                            $item->id_lamaran,
                            $item->alumni?->nama_alumni ?? '-',
                            $item->alumni?->nis ?? '-',
                            $item->alumni?->jurusan?->nama_jurusan ?? '-',
                            $item->lowongan?->judul_lowongan ?? '-',
                            $item->lowongan?->perusahaan?->nama_perusahaan ?? '-',
                            $item->status,
                            $item->tanggal_apply?->format('Y-m-d H:i:s'),
                            $item->tanggal_respon?->format('Y-m-d H:i:s') ?? '-',
                            $item->catatan ?? '-',
                            $item->catatan_admin ?? '-',
                        ]);
                    }
                });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export kesesuaian bidang data (CSV streamed).
     */
    public function exportKesesuaianBidang(array $filters = []): StreamedResponse
    {
        $headers = $this->csvHeaders('kesesuaian_bidang');

        $callback = function () use ($filters) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID Alumni', 'Nama Alumni', 'Jurusan SMK', 'Status Karier',
                'Detail Karier', 'Sesuai Bidang', 'Tahun Lulus',
            ]);

            $query = RiwayatStatus::with([
                'alumni.jurusan',
                'status',
                'pekerjaan.perusahaan',
                'kuliah.universitas',
                'kuliah.jurusanKuliah',
                'wirausaha.bidangUsaha',
            ])
            ->where('approval_status', 'approved')
            ->whereHas('status', function ($q) {
                $q->whereIn('nama_status', ['Bekerja', 'Kuliah', 'Wirausaha']);
            });

            if (!empty($filters['kesesuaian'])) {
                if ($filters['kesesuaian'] === 'sesuai') {
                    $query->where('is_sesuai_bidang', true);
                } elseif ($filters['kesesuaian'] === 'tidak_sesuai') {
                    $query->where('is_sesuai_bidang', false);
                }
            }
            if (!empty($filters['id_jurusan'])) {
                $query->whereHas('alumni', fn($q) => $q->where('id_jurusan', $filters['id_jurusan']));
            }

            $query->orderByDesc('updated_at')
                ->chunk(500, function ($items) use ($handle) {
                    foreach ($items as $item) {
                        $alumni = $item->alumni;
                        $sesuai = $item->is_sesuai_bidang === null
                            ? 'Belum Ditentukan'
                            : ($item->is_sesuai_bidang ? 'Sesuai' : 'Tidak Sesuai');

                        fputcsv($handle, [
                            $alumni?->id_alumni ?? '-',
                            $alumni?->nama_alumni ?? '-',
                            $alumni?->jurusan?->nama_jurusan ?? '-',
                            $item->status?->nama_status ?? '-',
                            $this->getCareerDetail($item),
                            $sesuai,
                            $alumni?->tahun_lulus?->format('Y') ?? '-',
                        ]);
                    }
                });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export lowongan data with application statistics (CSV streamed).
     */
    public function exportLowongan(array $filters = []): StreamedResponse
    {
        $headers = $this->csvHeaders('lowongan');

        $callback = function () use ($filters) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID Lowongan', 'Judul', 'Perusahaan', 'Tipe Pekerjaan',
                'Status', 'Approval', 'Total Pelamar', 'Diterima', 'Ditolak', 'Pending',
                'Tanggal Dibuat', 'Tanggal Selesai',
            ]);

            $query = Lowongan::with(['perusahaan'])
                ->withCount([
                    'lamaran',
                    'lamaran as lamaran_diterima_count' => fn($q) => $q->where('status', 'diterima'),
                    'lamaran as lamaran_ditolak_count' => fn($q) => $q->where('status', 'ditolak'),
                    'lamaran as lamaran_pending_count' => fn($q) => $q->where('status', 'pending'),
                ]);

            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            $query->orderByDesc('created_at')
                ->chunk(500, function ($items) use ($handle) {
                    foreach ($items as $item) {
                        fputcsv($handle, [
                            $item->id_lowongan,
                            $item->judul_lowongan,
                            $item->perusahaan?->nama_perusahaan ?? '-',
                            $item->tipe_pekerjaan ?? '-',
                            $item->status,
                            $item->approval_status,
                            $item->lamaran_count,
                            $item->lamaran_diterima_count,
                            $item->lamaran_ditolak_count,
                            $item->lamaran_pending_count,
                            $item->created_at?->format('Y-m-d H:i:s'),
                            $item->lowongan_selesai?->format('Y-m-d') ?? '-',
                        ]);
                    }
                });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // =====================
    // HELPERS
    // =====================

    private function csvHeaders(string $prefix): array
    {
        return [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $prefix . '_export_' . now()->format('Ymd_His') . '.csv"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];
    }

    private function getCareerDetail(?RiwayatStatus $riwayat): string
    {
        if (!$riwayat) return '-';

        $statusName = $riwayat->status?->nama_status ?? '';

        return match ($statusName) {
            'Bekerja' => ($riwayat->pekerjaan?->posisi ?? '-') . ' di ' . ($riwayat->pekerjaan?->perusahaan?->nama_perusahaan ?? '-'),
            'Kuliah' => ($riwayat->kuliah?->jurusanKuliah?->nama_jurusan ?? '-') . ' di ' . ($riwayat->kuliah?->universitas?->nama_universitas ?? '-'),
            'Wirausaha' => ($riwayat->wirausaha?->nama_usaha ?? '-') . ' (' . ($riwayat->wirausaha?->bidangUsaha?->nama_bidang ?? '-') . ')',
            default => '-',
        };
    }

    private function applyAlumniFilters($query, array $filters): void
    {
        if (!empty($filters['status_create'])) {
            $query->where('status_create', $filters['status_create']);
        }
        if (!empty($filters['id_jurusan'])) {
            $query->where('id_jurusan', $filters['id_jurusan']);
        }
        if (!empty($filters['tahun_lulus'])) {
            $query->whereYear('tahun_lulus', $filters['tahun_lulus']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nama_alumni', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%");
            });
        }
    }
}
