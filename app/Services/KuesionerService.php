<?php

namespace App\Services;

use App\Interfaces\KuesionerRepositoryInterface;

class KuesionerService
{
    private KuesionerRepositoryInterface $kuesionerRepository;

    public function __construct(KuesionerRepositoryInterface $kuesionerRepository)
    {
        $this->kuesionerRepository = $kuesionerRepository;
    }

    public function getAll(array $filters = [], int $perPage = 15)
    {
        return $this->kuesionerRepository->getAll($filters, $perPage);
    }

    public function getById(int $id)
    {
        return $this->kuesionerRepository->getById($id);
    }

    public function create(array $data)
    {
        return $this->kuesionerRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        return $this->kuesionerRepository->update($id, $data);
    }

    public function delete(int $id)
    {
        return $this->kuesionerRepository->delete($id);
    }

    public function addPertanyaan(int $kuesionerId, array $data)
    {
        return $this->kuesionerRepository->addPertanyaan($kuesionerId, $data);
    }

    public function updatePertanyaan(int $pertanyaanId, array $data)
    {
        return $this->kuesionerRepository->updatePertanyaan($pertanyaanId, $data);
    }

    public function deletePertanyaan(int $pertanyaanId)
    {
        return $this->kuesionerRepository->deletePertanyaan($pertanyaanId);
    }

    public function submitJawaban(int $userId, array $jawabanData)
    {
        return $this->kuesionerRepository->submitJawaban($userId, $jawabanData);
    }

    public function getPublished(int $perPage = 15)
    {
        return $this->kuesionerRepository->getPublished($perPage);
    }

    public function getWithPertanyaan(int $kuesionerId)
    {
        return $this->kuesionerRepository->getKuesionerWithPertanyaan($kuesionerId);
    }
}
