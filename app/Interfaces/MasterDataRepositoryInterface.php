<?php

namespace App\Interfaces;

interface MasterDataRepositoryInterface
{
    // Provinsi
    public function getAllProvinsi();
    public function getProvinsiById(int $id);
    public function createProvinsi(array $data);
    public function updateProvinsi(int $id, array $data);
    public function deleteProvinsi(int $id);

    // Kota
    public function getAllKota();
    public function getKotaByProvinsi(int $provinsiId);
    public function createKota(array $data);
    public function updateKota(int $id, array $data);
    public function deleteKota(int $id);

    // Jurusan (SMK)
    public function getAllJurusan();
    public function createJurusan(array $data);
    public function updateJurusan(int $id, array $data);
    public function deleteJurusan(int $id);

    // Jurusan Kuliah
    public function getAllJurusanKuliah();
    public function createJurusanKuliah(array $data);
    public function updateJurusanKuliah(int $id, array $data);
    public function deleteJurusanKuliah(int $id);

    // Skills
    public function getAllSkills();
    public function createSkill(array $data);
    public function updateSkill(int $id, array $data);
    public function deleteSkill(int $id);

    // Social Media
    public function getAllSocialMedia();
    public function createSocialMedia(array $data);
    public function updateSocialMedia(int $id, array $data);
    public function deleteSocialMedia(int $id);

    // Status
    public function getAllStatus();
    public function createStatus(array $data);
    public function updateStatus(int $id, array $data);
    public function deleteStatus(int $id);

    // Bidang Usaha
    public function getAllBidangUsaha();
    public function createBidangUsaha(array $data);
    public function updateBidangUsaha(int $id, array $data);
    public function deleteBidangUsaha(int $id);

    // Perusahaan
    public function getAllPerusahaan(array $filters = [], int $perPage = 15);
    public function getPerusahaanById(int $id);
    public function createPerusahaan(array $data);
    public function updatePerusahaan(int $id, array $data);
    public function deletePerusahaan(int $id);

    // Universitas
    public function getAllUniversitas();
    public function createUniversitas(array $data);

    // Export
    public function exportMasterData(string $type): array;
}
