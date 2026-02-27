<?php

namespace App\Repositories;

use App\Interfaces\MasterDataRepositoryInterface;
use App\Models\Provinsi;
use App\Models\Kota;
use App\Models\Jurusan;
use App\Models\JurusanKuliah;
use App\Models\Skill;
use App\Models\SocialMedia;
use App\Models\Status;
use App\Models\BidangUsaha;
use App\Models\Perusahaan;
use App\Models\Universitas;
use Illuminate\Support\Facades\Cache;

class MasterDataRepository implements MasterDataRepositoryInterface
{
    /**
     * Cache TTL in seconds (1 hour for master data)
     */
    private int $cacheTtl = 3600;
    // ─── Provinsi ────────────────────────────────────────

    public function getAllProvinsi()
    {
        return Cache::remember('master.provinsi', $this->cacheTtl, function () {
            return Provinsi::orderBy('nama_provinsi')->get();
        });
    }

    public function getProvinsiById(int $id)
    {
        return Provinsi::findOrFail($id);
    }

    public function getKotaByProvinsi(int $provinsiId)
    {
        return Cache::remember("master.kota.provinsi.{$provinsiId}", $this->cacheTtl, function () use ($provinsiId) {
            return Kota::where('id_provinsi', $provinsiId)
                ->orderBy('nama_kota')
                ->get();
        });
    }

    public function createProvinsi(array $data)
    {
        Cache::forget('master.provinsi');
        return Provinsi::create($data);
    }

    public function updateProvinsi(int $id, array $data)
    {
        $provinsi = Provinsi::findOrFail($id);
        $provinsi->update($data);
        Cache::forget('master.provinsi');
        return $provinsi->fresh();
    }

    public function deleteProvinsi(int $id)
    {
        Provinsi::findOrFail($id)->delete();
        Cache::forget('master.provinsi');
        return true;
    }

    // ─── Kota ────────────────────────────────────────────

    public function getAllKota()
    {
        return Cache::remember('master.kota.all', $this->cacheTtl, function () {
            return Kota::with('provinsi')->orderBy('nama_kota')->get();
        });
    }

    public function createKota(array $data)
    {
        $this->clearKotaCache();
        return Kota::create($data);
    }

    public function updateKota(int $id, array $data)
    {
        $kota = Kota::findOrFail($id);
        $kota->update($data);
        $this->clearKotaCache();
        return $kota->fresh();
    }

    public function deleteKota(int $id)
    {
        Kota::findOrFail($id)->delete();
        $this->clearKotaCache();
        return true;
    }

    private function clearKotaCache(): void
    {
        Cache::forget('master.kota.all');
        // Clear provinsi-specific kota caches
        $provinsiIds = Provinsi::pluck('id_provinsi');
        foreach ($provinsiIds as $id) {
            Cache::forget("master.kota.provinsi.{$id}");
        }
    }

    // ─── Jurusan (SMK) ──────────────────────────────────

    public function getAllJurusan()
    {
        return Cache::remember('master.jurusan', $this->cacheTtl, function () {
            return Jurusan::orderBy('nama_jurusan')->get();
        });
    }

    public function createJurusan(array $data)
    {
        Cache::forget('master.jurusan');
        return Jurusan::create($data);
    }

    public function updateJurusan(int $id, array $data)
    {
        $jurusan = Jurusan::findOrFail($id);
        $jurusan->update($data);
        Cache::forget('master.jurusan');
        return $jurusan->fresh();
    }

    public function deleteJurusan(int $id)
    {
        Jurusan::findOrFail($id)->delete();
        Cache::forget('master.jurusan');
        return true;
    }

    // ─── Jurusan Kuliah ─────────────────────────────────

    public function getAllJurusanKuliah()
    {
        return JurusanKuliah::with('universitas')->orderBy('nama_jurusan')->get();
    }

    public function createJurusanKuliah(array $data)
    {
        return JurusanKuliah::create($data);
    }

    public function updateJurusanKuliah(int $id, array $data)
    {
        $jurusan = JurusanKuliah::findOrFail($id);
        $jurusan->update($data);
        return $jurusan->fresh();
    }

    public function deleteJurusanKuliah(int $id)
    {
        JurusanKuliah::findOrFail($id)->delete();
        return true;
    }

    // ─── Skills ─────────────────────────────────────────

    public function getAllSkills()
    {
        return Cache::remember('master.skills', $this->cacheTtl, function () {
            return Skill::orderBy('name_skills')->get();
        });
    }

    public function createSkill(array $data)
    {
        Cache::forget('master.skills');
        return Skill::create($data);
    }

    public function updateSkill(int $id, array $data)
    {
        $skill = Skill::findOrFail($id);
        $skill->update($data);
        Cache::forget('master.skills');
        return $skill->fresh();
    }

    public function deleteSkill(int $id)
    {
        Skill::findOrFail($id)->delete();
        Cache::forget('master.skills');
        return true;
    }

    // ─── Social Media ───────────────────────────────────

    public function getAllSocialMedia()
    {
        return Cache::remember('master.social_media', $this->cacheTtl, function () {
            return SocialMedia::orderBy('nama_sosmed')->get();
        });
    }

    public function createSocialMedia(array $data)
    {
        Cache::forget('master.social_media');
        return SocialMedia::create($data);
    }

    public function updateSocialMedia(int $id, array $data)
    {
        $socialMedia = SocialMedia::findOrFail($id);
        $socialMedia->update($data);
        Cache::forget('master.social_media');
        return $socialMedia->fresh();
    }

    public function deleteSocialMedia(int $id)
    {
        SocialMedia::findOrFail($id)->delete();
        Cache::forget('master.social_media');
        return true;
    }

    // ─── Status ─────────────────────────────────────────

    public function getAllStatus()
    {
        return Cache::remember('master.status', $this->cacheTtl, function () {
            return Status::all();
        });
    }

    public function createStatus(array $data)
    {
        Cache::forget('master.status');
        return Status::create($data);
    }

    public function updateStatus(int $id, array $data)
    {
        $status = Status::findOrFail($id);
        $status->update($data);
        Cache::forget('master.status');
        return $status->fresh();
    }

    public function deleteStatus(int $id)
    {
        Status::findOrFail($id)->delete();
        Cache::forget('master.status');
        return true;
    }

    // ─── Bidang Usaha ───────────────────────────────────

    public function getAllBidangUsaha()
    {
        return Cache::remember('master.bidang_usaha', $this->cacheTtl, function () {
            return BidangUsaha::orderBy('nama_bidang')->get();
        });
    }

    public function createBidangUsaha(array $data)
    {
        Cache::forget('master.bidang_usaha');
        return BidangUsaha::create($data);
    }

    public function updateBidangUsaha(int $id, array $data)
    {
        $bidang = BidangUsaha::findOrFail($id);
        $bidang->update($data);
        Cache::forget('master.bidang_usaha');
        return $bidang->fresh();
    }

    public function deleteBidangUsaha(int $id)
    {
        BidangUsaha::findOrFail($id)->delete();
        Cache::forget('master.bidang_usaha');
        return true;
    }

    // ─── Perusahaan ─────────────────────────────────────

    public function getAllPerusahaan(array $filters = [], int $perPage = 15)
    {
        $query = Perusahaan::with(['kota.provinsi']);

        if (!empty($filters['search'])) {
            $query->where('nama_perusahaan', 'like', "%{$filters['search']}%");
        }

        if (!empty($filters['id_kota'])) {
            $query->where('id_kota', $filters['id_kota']);
        }

        return $query->orderBy('nama_perusahaan')->paginate($perPage);
    }

    public function getPerusahaanById(int $id)
    {
        return Perusahaan::with(['kota.provinsi'])->findOrFail($id);
    }

    public function createPerusahaan(array $data)
    {
        return Perusahaan::create($data);
    }

    public function updatePerusahaan(int $id, array $data)
    {
        $perusahaan = Perusahaan::findOrFail($id);
        $perusahaan->update($data);
        return $perusahaan->fresh();
    }

    public function deletePerusahaan(int $id)
    {
        Perusahaan::findOrFail($id)->delete();
        return true;
    }

    // ─── Universitas ────────────────────────────────────

    public function getAllUniversitas()
    {
        return Universitas::with('jurusanKuliah')->orderBy('nama_universitas')->get();
    }

    public function createUniversitas(array $data)
    {
        return Universitas::create($data);
    }

    // ─── Export ──────────────────────────────────────────

    public function exportMasterData(string $type): array
    {
        switch ($type) {
            case 'jurusan':
                return Jurusan::orderBy('nama_jurusan')
                    ->get()
                    ->map(fn($j) => [
                        'id' => $j->id_jurusan,
                        'nama' => $j->nama_jurusan,
                    ])
                    ->toArray();

            case 'perusahaan':
                return Perusahaan::with(['kota.provinsi'])
                    ->orderBy('nama_perusahaan')
                    ->get()
                    ->map(fn($p) => [
                        'id' => $p->id_perusahaan,
                        'nama' => $p->nama_perusahaan,
                        'alamat' => $p->jalan ?? '-',
                        'kota' => $p->kota?->nama_kota ?? '-',
                        'provinsi' => $p->kota?->provinsi?->nama_provinsi ?? '-',
                    ])
                    ->toArray();

            default:
                return [];
        }
    }
}
