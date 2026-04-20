<?php

namespace App\Repositories;

use App\Interfaces\StatusKarierRepositoryInterface;
use App\Models\BidangUsaha;
use App\Models\JurusanKuliah;
use App\Models\Pekerjaan;
use App\Models\RiwayatStatus;
use App\Models\Universitas;
use App\Models\Wirausaha;

class StatusKarierRepository implements StatusKarierRepositoryInterface
{
    // ═══════════════════════════════════════════════
    //  UNIVERSITAS
    // ═══════════════════════════════════════════════

    public function getAllUniversitas()
    {
        return Universitas::with(['jurusanKuliah', 'kota.provinsi'])
            ->orderBy('nama_universitas')
            ->get();
    }

    public function createUniversitas(array $data)
    {
        return Universitas::create([
            'nama_universitas' => $data['nama'] ?? $data['nama_universitas'],
            'alamat' => $data['alamat'] ?? null,
            'id_kota' => $data['id_kota'] ?? null,
        ]);
    }

    public function updateUniversitas(int $id, array $data)
    {
        $univ = Universitas::findOrFail($id);
        $updateData = [];

        if (isset($data['nama']) || isset($data['nama_universitas'])) {
            $updateData['nama_universitas'] = $data['nama'] ?? $data['nama_universitas'];
        }
        if (array_key_exists('alamat', $data)) {
            $updateData['alamat'] = $data['alamat'];
        }
        if (array_key_exists('id_kota', $data)) {
            $updateData['id_kota'] = $data['id_kota'];
        }

        $univ->update($updateData);
        return $univ->fresh(['jurusanKuliah', 'kota.provinsi']);
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
        return JurusanKuliah::with('universitas')
            ->orderBy('nama_jurusan')
            ->get();
    }

    public function createProdi(array $data)
    {
        return JurusanKuliah::create([
            'nama_jurusan' => $data['nama_prodi'] ?? $data['nama_jurusan'] ?? $data['nama'],
            'id_universitas' => $data['id_universitas'] ?? null,
        ]);
    }

    public function updateProdi(int $id, array $data)
    {
        $prodi = JurusanKuliah::findOrFail($id);
        $updateData = [
            'nama_jurusan' => $data['nama_prodi'] ?? $data['nama_jurusan'] ?? $data['nama'] ?? $prodi->nama_jurusan,
        ];
        if (array_key_exists('id_universitas', $data)) {
            $updateData['id_universitas'] = $data['id_universitas'];
        }
        $prodi->update($updateData);
        return $prodi->fresh('universitas');
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
    //  DATA WIRAUSAHA
    // ═══════════════════════════════════════════════

    public function getAllWirausaha(?string $search = null)
    {
        $query = Wirausaha::with([
            'bidangUsaha',
            'kota.provinsi',
            'riwayatStatus.alumni.jurusan',
        ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_usaha', 'like', "%{$search}%")
                  ->orWhere('alamat', 'like', "%{$search}%")
                  ->orWhereHas('bidangUsaha', function ($q2) use ($search) {
                      $q2->where('nama_bidang', 'like', "%{$search}%");
                  })
                  ->orWhereHas('riwayatStatus.alumni', function ($q2) use ($search) {
                      $q2->where('nama_alumni', 'like', "%{$search}%")
                         ->orWhere('nis', 'like', "%{$search}%");
                  })
                  ->orWhereHas('kota', function ($q2) use ($search) {
                      $q2->where('nama_kota', 'like', "%{$search}%");
                  });
            });
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function createWirausaha(array $data)
    {
        $wirausaha = Wirausaha::create([
            'nama_usaha' => $data['nama_usaha'],
            'id_bidang'  => $data['id_bidang'],
            'alamat'     => $data['alamat'] ?? null,
            'id_kota'    => $data['id_kota'] ?? null,
            'id_riwayat' => $data['id_riwayat'],
            'latitude'   => $data['latitude'] ?? null,
            'longitude'  => $data['longitude'] ?? null,
        ]);

        return $wirausaha->fresh([
            'bidangUsaha',
            'kota.provinsi',
            'riwayatStatus.alumni.jurusan',
        ]);
    }

    public function updateWirausaha(int $id, array $data)
    {
        $wirausaha = Wirausaha::findOrFail($id);

        $updateData = [];
        if (isset($data['nama_usaha'])) {
            $updateData['nama_usaha'] = $data['nama_usaha'];
        }
        if (isset($data['id_bidang'])) {
            $updateData['id_bidang'] = $data['id_bidang'];
        }
        if (array_key_exists('alamat', $data)) {
            $updateData['alamat'] = $data['alamat'];
        }
        if (array_key_exists('id_kota', $data)) {
            $updateData['id_kota'] = $data['id_kota'];
        }
        if (array_key_exists('latitude', $data)) {
            $updateData['latitude'] = $data['latitude'];
        }
        if (array_key_exists('longitude', $data)) {
            $updateData['longitude'] = $data['longitude'];
        }

        $wirausaha->update($updateData);

        return $wirausaha->fresh([
            'bidangUsaha',
            'kota.provinsi',
            'riwayatStatus.alumni.jurusan',
        ]);
    }

    public function deleteWirausaha(int $id)
    {
        Wirausaha::findOrFail($id)->delete();
        return true;
    }



    // ═══════════════════════════════════════════════
    //  REPORT / STATISTICS
    // ═══════════════════════════════════════════════

    public function getStatusDistribution(): array
    {
        return RiwayatStatus::selectRaw('id_status, count(*) as total')
            ->whereNull('tahun_selesai')
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
                        'jurusan' => $u->jurusanKuliah->pluck('nama_jurusan')->implode(', ') ?: '-',
                    ])
                    ->toArray();

            case 'prodi':
                return JurusanKuliah::with('universitas')
                    ->orderBy('nama_jurusan')
                    ->get()
                    ->map(fn($p) => [
                        'id' => $p->id_jurusanKuliah,
                        'nama' => $p->nama_jurusan,
                    ])
                    ->toArray();

            case 'wirausaha':
                return Wirausaha::with([
                        'bidangUsaha',
                        'kota.provinsi',
                        'riwayatStatus.alumni.jurusan',
                    ])
                    ->orderByDesc('created_at')
                    ->get()
                    ->map(fn($w, $i) => [
                        'no'           => $i + 1,
                        'nama_alumni'  => $w->riwayatStatus?->alumni?->nama_alumni ?? '-',
                        'nis'          => $w->riwayatStatus?->alumni?->nis ?? '-',
                        'nama_usaha'   => $w->nama_usaha,
                        'bidang_usaha' => $w->bidangUsaha?->nama_bidang ?? '-',
                        'alamat'       => $w->alamat ?? '-',
                        'kota'         => $w->kota?->nama_kota ?? '-',
                        'provinsi'     => $w->kota?->provinsi?->nama_provinsi ?? '-',
                    ])
                    ->toArray();

            default:
                return [];
        }
    }
}

