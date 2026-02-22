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

class MasterDataRepository implements MasterDataRepositoryInterface
{
    // ─── Provinsi ────────────────────────────────────────

    public function getAllProvinsi()
    {
        return Provinsi::orderBy('nama_provinsi')->get();
    }

    public function getProvinsiById(int $id)
    {
        return Provinsi::findOrFail($id);
    }

    public function getKotaByProvinsi(int $provinsiId)
    {
        return Kota::where('id_provinsi', $provinsiId)
            ->orderBy('nama_kota')
            ->get();
    }

    public function createProvinsi(array $data)
    {
        return Provinsi::create($data);
    }

    public function updateProvinsi(int $id, array $data)
    {
        $provinsi = Provinsi::findOrFail($id);
        $provinsi->update($data);
        return $provinsi->fresh();
    }

    public function deleteProvinsi(int $id)
    {
        Provinsi::findOrFail($id)->delete();
        return true;
    }

    // ─── Kota ────────────────────────────────────────────

    public function getAllKota()
    {
        return Kota::with('provinsi')->orderBy('nama_kota')->get();
    }

    public function createKota(array $data)
    {
        return Kota::create($data);
    }

    public function updateKota(int $id, array $data)
    {
        $kota = Kota::findOrFail($id);
        $kota->update($data);
        return $kota->fresh();
    }

    public function deleteKota(int $id)
    {
        Kota::findOrFail($id)->delete();
        return true;
    }

    // ─── Jurusan (SMK) ──────────────────────────────────

    public function getAllJurusan()
    {
        return Jurusan::orderBy('nama_jurusan')->get();
    }

    public function createJurusan(array $data)
    {
        return Jurusan::create($data);
    }

    public function updateJurusan(int $id, array $data)
    {
        $jurusan = Jurusan::findOrFail($id);
        $jurusan->update($data);
        return $jurusan->fresh();
    }

    public function deleteJurusan(int $id)
    {
        Jurusan::findOrFail($id)->delete();
        return true;
    }

    // ─── Jurusan Kuliah ─────────────────────────────────

    public function getAllJurusanKuliah()
    {
        return JurusanKuliah::orderBy('nama_jurusan')->get();
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
        return Skill::orderBy('name_skills')->get();
    }

    public function createSkill(array $data)
    {
        return Skill::create($data);
    }

    public function updateSkill(int $id, array $data)
    {
        $skill = Skill::findOrFail($id);
        $skill->update($data);
        return $skill->fresh();
    }

    public function deleteSkill(int $id)
    {
        Skill::findOrFail($id)->delete();
        return true;
    }

    // ─── Social Media ───────────────────────────────────

    public function getAllSocialMedia()
    {
        return SocialMedia::orderBy('nama_sosmed')->get();
    }

    public function createSocialMedia(array $data)
    {
        return SocialMedia::create($data);
    }

    public function updateSocialMedia(int $id, array $data)
    {
        $socialMedia = SocialMedia::findOrFail($id);
        $socialMedia->update($data);
        return $socialMedia->fresh();
    }

    public function deleteSocialMedia(int $id)
    {
        SocialMedia::findOrFail($id)->delete();
        return true;
    }

    // ─── Status ─────────────────────────────────────────

    public function getAllStatus()
    {
        return Status::all();
    }

    public function createStatus(array $data)
    {
        return Status::create($data);
    }

    public function updateStatus(int $id, array $data)
    {
        $status = Status::findOrFail($id);
        $status->update($data);
        return $status->fresh();
    }

    public function deleteStatus(int $id)
    {
        Status::findOrFail($id)->delete();
        return true;
    }

    // ─── Bidang Usaha ───────────────────────────────────

    public function getAllBidangUsaha()
    {
        return BidangUsaha::orderBy('nama_bidang')->get();
    }

    public function createBidangUsaha(array $data)
    {
        return BidangUsaha::create($data);
    }

    public function updateBidangUsaha(int $id, array $data)
    {
        $bidang = BidangUsaha::findOrFail($id);
        $bidang->update($data);
        return $bidang->fresh();
    }

    public function deleteBidangUsaha(int $id)
    {
        BidangUsaha::findOrFail($id)->delete();
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
}
