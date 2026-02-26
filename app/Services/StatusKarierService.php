<?php

namespace App\Services;

use App\Interfaces\StatusKarierRepositoryInterface;

class StatusKarierService
{
    private StatusKarierRepositoryInterface $repository;

    public function __construct(StatusKarierRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    // Universitas
    public function getAllUniversitas()
    {
        return $this->repository->getAllUniversitas();
    }

    public function createUniversitas(array $data)
    {
        return $this->repository->createUniversitas($data);
    }

    public function updateUniversitas(int $id, array $data)
    {
        return $this->repository->updateUniversitas($id, $data);
    }

    public function deleteUniversitas(int $id)
    {
        return $this->repository->deleteUniversitas($id);
    }

    // Program Studi
    public function getAllProdi()
    {
        return $this->repository->getAllProdi();
    }

    public function createProdi(array $data)
    {
        return $this->repository->createProdi($data);
    }

    public function updateProdi(int $id, array $data)
    {
        return $this->repository->updateProdi($id, $data);
    }

    public function deleteProdi(int $id)
    {
        return $this->repository->deleteProdi($id);
    }

    // Bidang Usaha
    public function getAllBidangUsaha()
    {
        return $this->repository->getAllBidangUsaha();
    }

    public function createBidangUsaha(array $data)
    {
        return $this->repository->createBidangUsaha($data);
    }

    public function updateBidangUsaha(int $id, array $data)
    {
        return $this->repository->updateBidangUsaha($id, $data);
    }

    public function deleteBidangUsaha(int $id)
    {
        return $this->repository->deleteBidangUsaha($id);
    }

    // Report / Stats
    public function getStatusDistribution(): array
    {
        return $this->repository->getStatusDistribution();
    }

    public function exportStatusReport(string $type): array
    {
        return $this->repository->exportStatusReport($type);
    }
}
