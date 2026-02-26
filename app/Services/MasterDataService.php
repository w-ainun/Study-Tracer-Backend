<?php

namespace App\Services;

use App\Interfaces\MasterDataRepositoryInterface;

class MasterDataService
{
    private MasterDataRepositoryInterface $masterDataRepository;

    public function __construct(MasterDataRepositoryInterface $masterDataRepository)
    {
        $this->masterDataRepository = $masterDataRepository;
    }

    // ─── Provinsi ────────────────────────────────────────

    public function getAllProvinsi()
    {
        return $this->masterDataRepository->getAllProvinsi();
    }

    public function getProvinsiById(int $id)
    {
        return $this->masterDataRepository->getProvinsiById($id);
    }

    public function getKotaByProvinsi(int $provinsiId)
    {
        return $this->masterDataRepository->getKotaByProvinsi($provinsiId);
    }

    public function createProvinsi(array $data)
    {
        return $this->masterDataRepository->createProvinsi($data);
    }

    public function updateProvinsi(int $id, array $data)
    {
        return $this->masterDataRepository->updateProvinsi($id, $data);
    }

    public function deleteProvinsi(int $id)
    {
        return $this->masterDataRepository->deleteProvinsi($id);
    }

    // ─── Kota ────────────────────────────────────────────

    public function getAllKota()
    {
        return $this->masterDataRepository->getAllKota();
    }

    public function createKota(array $data)
    {
        return $this->masterDataRepository->createKota($data);
    }

    public function updateKota(int $id, array $data)
    {
        return $this->masterDataRepository->updateKota($id, $data);
    }

    public function deleteKota(int $id)
    {
        return $this->masterDataRepository->deleteKota($id);
    }

    // ─── Jurusan (SMK) ──────────────────────────────────

    public function getAllJurusan()
    {
        return $this->masterDataRepository->getAllJurusan();
    }

    public function createJurusan(array $data)
    {
        return $this->masterDataRepository->createJurusan($data);
    }

    public function updateJurusan(int $id, array $data)
    {
        return $this->masterDataRepository->updateJurusan($id, $data);
    }

    public function deleteJurusan(int $id)
    {
        return $this->masterDataRepository->deleteJurusan($id);
    }

    // ─── Jurusan Kuliah ─────────────────────────────────

    public function getAllJurusanKuliah()
    {
        return $this->masterDataRepository->getAllJurusanKuliah();
    }

    public function createJurusanKuliah(array $data)
    {
        return $this->masterDataRepository->createJurusanKuliah($data);
    }

    public function updateJurusanKuliah(int $id, array $data)
    {
        return $this->masterDataRepository->updateJurusanKuliah($id, $data);
    }

    public function deleteJurusanKuliah(int $id)
    {
        return $this->masterDataRepository->deleteJurusanKuliah($id);
    }

    // ─── Skills ─────────────────────────────────────────

    public function getAllSkills()
    {
        return $this->masterDataRepository->getAllSkills();
    }

    public function createSkill(array $data)
    {
        return $this->masterDataRepository->createSkill($data);
    }

    public function updateSkill(int $id, array $data)
    {
        return $this->masterDataRepository->updateSkill($id, $data);
    }

    public function deleteSkill(int $id)
    {
        return $this->masterDataRepository->deleteSkill($id);
    }

    // ─── Social Media ───────────────────────────────────

    public function getAllSocialMedia()
    {
        return $this->masterDataRepository->getAllSocialMedia();
    }

    public function createSocialMedia(array $data)
    {
        return $this->masterDataRepository->createSocialMedia($data);
    }

    public function updateSocialMedia(int $id, array $data)
    {
        return $this->masterDataRepository->updateSocialMedia($id, $data);
    }

    public function deleteSocialMedia(int $id)
    {
        return $this->masterDataRepository->deleteSocialMedia($id);
    }

    // ─── Status ─────────────────────────────────────────

    public function getAllStatus()
    {
        return $this->masterDataRepository->getAllStatus();
    }

    public function createStatus(array $data)
    {
        return $this->masterDataRepository->createStatus($data);
    }

    public function updateStatus(int $id, array $data)
    {
        return $this->masterDataRepository->updateStatus($id, $data);
    }

    public function deleteStatus(int $id)
    {
        return $this->masterDataRepository->deleteStatus($id);
    }

    // ─── Bidang Usaha ───────────────────────────────────

    public function getAllBidangUsaha()
    {
        return $this->masterDataRepository->getAllBidangUsaha();
    }

    public function createBidangUsaha(array $data)
    {
        return $this->masterDataRepository->createBidangUsaha($data);
    }

    public function updateBidangUsaha(int $id, array $data)
    {
        return $this->masterDataRepository->updateBidangUsaha($id, $data);
    }

    public function deleteBidangUsaha(int $id)
    {
        return $this->masterDataRepository->deleteBidangUsaha($id);
    }

    // ─── Perusahaan ─────────────────────────────────────

    public function getAllPerusahaan(array $filters = [], int $perPage = 15)
    {
        return $this->masterDataRepository->getAllPerusahaan($filters, $perPage);
    }

    public function getPerusahaanById(int $id)
    {
        return $this->masterDataRepository->getPerusahaanById($id);
    }

    public function createPerusahaan(array $data)
    {
        return $this->masterDataRepository->createPerusahaan($data);
    }

    public function updatePerusahaan(int $id, array $data)
    {
        return $this->masterDataRepository->updatePerusahaan($id, $data);
    }

    public function deletePerusahaan(int $id)
    {
        return $this->masterDataRepository->deletePerusahaan($id);
    }

    // ─── Universitas ────────────────────────────────────

    public function getAllUniversitas()
    {
        return $this->masterDataRepository->getAllUniversitas();
    }

    public function createUniversitas(array $data)
    {
        return $this->masterDataRepository->createUniversitas($data);
    }

    // ─── Export ──────────────────────────────────────────

    public function exportMasterData(string $type): array
    {
        return $this->masterDataRepository->exportMasterData($type);
    }
}
