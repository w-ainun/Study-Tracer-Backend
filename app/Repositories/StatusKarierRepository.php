<?php

namespace App\Repositories;

use App\Interfaces\StatusKarierRepositoryInterface;
use App\Models\BidangUsaha;
use App\Models\JurusanKuliah;
use App\Models\Pekerjaan;
use App\Models\RiwayatStatus;
use App\Models\Universitas;

class StatusKarierRepository implements StatusKarierRepositoryInterface
{
    // ═══════════════════════════════════════════════
    //  UNIVERSITAS
    // ═══════════════════════════════════════════════

    public function getAllUniversitas()
    {
        return Universitas::with('jurusanKuliah')
            ->orderBy('nama_universitas')
            ->get();
    }

    public function createUniversitas(array $data)
    {
        return Universitas::create([
            'nama_universitas' => $data['nama'] ?? $data['nama_universitas'],
        ]);
    }

    public function updateUniversitas(int $id, array $data)
    {
        $univ = Universitas::findOrFail($id);
        $updateData = [];

        if (isset($data['nama']) || isset($data['nama_universitas'])) {
            $updateData['nama_universitas'] = $data['nama'] ?? $data['nama_universitas'];
        }

        $univ->update($updateData);
        return $univ->fresh();
    }

    public function deleteUniversitas(int $id)
    {
        Universitas::findOrFail($id)->delete();
        return true;
    }

    // ═══════════════════════════════════════════════
    //  PROGRAM STUDI (JURUSAN KULIAH)
    // ═══════════════════════════════════════════════

    public function getAllProdi()
    {
        return JurusanKuliah::orderBy('nama_jurusan')->get();
    }

    public function createProdi(array $data)
    {
        return JurusanKuliah::create([
            'nama_jurusan' => $data['nama_prodi'] ?? $data['nama_jurusan'] ?? $data['nama'],
        ]);
    }

    public function updateProdi(int $id, array $data)
    {
        $prodi = JurusanKuliah::findOrFail($id);
        $prodi->update([
            'nama_jurusan' => $data['nama_prodi'] ?? $data['nama_jurusan'] ?? $data['nama'] ?? $prodi->nama_jurusan,
        ]);
        return $prodi->fresh();
    }

    public function deleteProdi(int $id)
    {
        JurusanKuliah::findOrFail($id)->delete();
        return true;
    }

    // ═══════════════════════════════════════════════
    //  BIDANG USAHA (WIRAUSAHA)
    // ═══════════════════════════════════════════════

    public function getAllBidangUsaha()
    {
        return BidangUsaha::orderBy('nama_bidang')->get();
    }

    public function createBidangUsaha(array $data)
    {
        return BidangUsaha::create([
            'nama_bidang' => $data['nama_bidang'] ?? $data['nama'],
        ]);
    }

    public function updateBidangUsaha(int $id, array $data)
    {
        $bidang = BidangUsaha::findOrFail($id);
        $bidang->update([
            'nama_bidang' => $data['nama_bidang'] ?? $data['nama'] ?? $bidang->nama_bidang,
        ]);
        return $bidang->fresh();
    }

    public function deleteBidangUsaha(int $id)
    {
        BidangUsaha::findOrFail($id)->delete();
        return true;
    }

    // ═══════════════════════════════════════════════
    //  POSISI PEKERJAAN (distinct from pekerjaan.posisi)
    // ═══════════════════════════════════════════════

    public function getAllPosisi()
    {
        return Pekerjaan::select('posisi')
            ->distinct()
            ->orderBy('posisi')
            ->pluck('posisi')
            ->map(fn(string $posisi, int $index) => [
                'id' => $index + 1,
                'nama' => $posisi,
            ])
            ->values()
            ->toArray();
    }

    // ═══════════════════════════════════════════════
    //  REPORT / STATISTICS
    // ═══════════════════════════════════════════════

    public function getStatusDistribution(): array
    {
        return RiwayatStatus::selectRaw('id_status, count(*) as total')
            ->groupBy('id_status')
            ->with('status')
            ->get()
            ->map(fn($item) => [
                'status' => $item->status->nama_status ?? 'Unknown',
                'total' => $item->total,
            ])
            ->toArray();
    }

    public function exportStatusReport(string $type): array
    {
        switch ($type) {
            case 'universitas':
                return Universitas::with('jurusanKuliah')
                    ->orderBy('nama_universitas')
                    ->get()
                    ->map(fn($u) => [
                        'id' => $u->id_universitas,
                        'nama' => $u->nama_universitas,
                        'jurusan' => $u->jurusanKuliah?->nama_jurusan ?? '-',
                    ])
                    ->toArray();

            case 'prodi':
                return JurusanKuliah::orderBy('nama_jurusan')
                    ->get()
                    ->map(fn($p) => [
                        'id' => $p->id_jurusanKuliah,
                        'nama' => $p->nama_jurusan,
                    ])
                    ->toArray();

            case 'wirausaha':
                return BidangUsaha::orderBy('nama_bidang')
                    ->get()
                    ->map(fn($b) => [
                        'id' => $b->id_bidang,
                        'nama' => $b->nama_bidang,
                    ])
                    ->toArray();

            case 'posisi':
                return Pekerjaan::select('posisi')
                    ->distinct()
                    ->orderBy('posisi')
                    ->get()
                    ->values()
                    ->map(fn($p, $i) => [
                        'id' => $i + 1,
                        'nama' => $p->posisi,
                    ])
                    ->toArray();

            default:
                return [];
        }
    }
}
