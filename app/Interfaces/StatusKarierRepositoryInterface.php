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

    // Posisi Pekerjaan
    public function getAllPosisi();
    public function createPosisi(array $data);
    public function updatePosisi(int $id, array $data);
    public function deletePosisi(int $id);

    // Report / Stats
    public function getStatusDistribution(): array;
    public function exportStatusReport(string $type): array;
}
