<?php

namespace App\Interfaces;

interface StatusKarierRepositoryInterface
{
    // Referensi Universitas
    public function getAllUniversitas();
    public function createUniversitas(array $data);
    public function updateUniversitas(int $id, array $data);
    public function deleteUniversitas(int $id);

    // Jurusan Kuliah (Program Studi)
    public function getAllProdi();
    public function createProdi(array $data);
    public function updateProdi(int $id, array $data);
    public function deleteProdi(int $id);

    // Bidang Usaha (Wirausaha)
    public function getAllBidangUsaha();
    public function createBidangUsaha(array $data);
    public function updateBidangUsaha(int $id, array $data);
    public function deleteBidangUsaha(int $id);

    // Data Wirausaha
    public function getAllWirausaha(?string $search = null);
    public function createWirausaha(array $data);
    public function updateWirausaha(int $id, array $data);
    public function deleteWirausaha(int $id);

    // Report / Stats
    public function getStatusDistribution(): array;
    public function exportStatusReport(string $type): array;
}
