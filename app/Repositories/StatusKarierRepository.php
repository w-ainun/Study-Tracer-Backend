<?php

namespace App\Repositories;

use App\Interfaces\StatusKarierRepositoryInterface;
use App\Models\BidangUsaha;
use App\Models\JurusanKuliah;
use App\Models\Posisi;
use App\Models\ReferensiUniversitas;
use App\Models\RiwayatStatus;

class StatusKarierRepository implements StatusKarierRepositoryInterface
{
    // ═══════════════════════════════════════════════
    //  REFERENSI UNIVERSITAS
    // ═══════════════════════════════════════════════

    public function getAllUniversitas()
    {
        return ReferensiUniversitas::orderBy('nama_universitas')->get();
    }

    public function createUniversitas(array $data)
    {
        return ReferensiUniversitas::create([
            'nama_universitas' => $data['nama'] ?? $data['nama_universitas'],
            'jurusan' => $data['jurusan'] ?? [],
        ]);
    }

    public function updateUniversitas(int $id, array $data)
    {
        $univ = ReferensiUniversitas::findOrFail($id);
        $updateData = [];

        if (isset($data['nama']) || isset($data['nama_universitas'])) {
            $updateData['nama_universitas'] = $data['nama'] ?? $data['nama_universitas'];
        }
        if (array_key_exists('jurusan', $data)) {
            $updateData['jurusan'] = $data['jurusan'];
        }

        $univ->update($updateData);
        return $univ->fresh();
    }

    public function deleteUniversitas(int $id)
    {
        ReferensiUniversitas::findOrFail($id)->delete();
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
            'nama_jurusan' => $data['nama'] ?? $data['nama_jurusan'],
        ]);
    }

    public function updateProdi(int $id, array $data)
    {
        $prodi = JurusanKuliah::findOrFail($id);
        $prodi->update([
            'nama_jurusan' => $data['nama'] ?? $data['nama_jurusan'] ?? $prodi->nama_jurusan,
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
    //  POSISI PEKERJAAN
    // ═══════════════════════════════════════════════

    public function getAllPosisi()
    {
        return Posisi::orderBy('nama_posisi')->get();
    }

    public function createPosisi(array $data)
    {
        return Posisi::create([
            'nama_posisi' => $data['nama_posisi'] ?? $data['nama'],
        ]);
    }

    public function updatePosisi(int $id, array $data)
    {
        $posisi = Posisi::findOrFail($id);
        $posisi->update([
            'nama_posisi' => $data['nama_posisi'] ?? $data['nama'] ?? $posisi->nama_posisi,
        ]);
        return $posisi->fresh();
    }

    public function deletePosisi(int $id)
    {
        Posisi::findOrFail($id)->delete();
        return true;
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
                return ReferensiUniversitas::orderBy('nama_universitas')
                    ->get()
                    ->map(fn($u) => [
                        'id' => $u->id_ref_univ,
                        'nama' => $u->nama_universitas,
                        'jurusan' => implode(', ', $u->jurusan ?? []),
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
                return Posisi::orderBy('nama_posisi')
                    ->get()
                    ->map(fn($p) => [
                        'id' => $p->id_posisi,
                        'nama' => $p->nama_posisi,
                    ])
                    ->toArray();

            default:
                return [];
        }
    }
}
