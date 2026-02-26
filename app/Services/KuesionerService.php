<?php

namespace App\Services;

use App\Interfaces\KuesionerRepositoryInterface;
use App\Interfaces\SectionQuesRepositoryInterface;

class KuesionerService
{
    private KuesionerRepositoryInterface $kuesionerRepository;
    private SectionQuesRepositoryInterface $sectionQuesRepository;

    public function __construct(
        KuesionerRepositoryInterface $kuesionerRepository,
        SectionQuesRepositoryInterface $sectionQuesRepository
    ) {
        $this->kuesionerRepository = $kuesionerRepository;
        $this->sectionQuesRepository = $sectionQuesRepository;
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

    public function getAllPertanyaan(array $filters = [], int $perPage = 15)
    {
        return $this->kuesionerRepository->getAllPertanyaan($filters, $perPage);
    }

    public function addPertanyaan(int $kuesionerId, array $data)
    {
        return $this->kuesionerRepository->addPertanyaan($kuesionerId, $data);
    }

    public function storePertanyaan(array $data)
    {
        return $this->kuesionerRepository->storePertanyaan($data);
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

    public function getPublishedByStatus(int $statusId)
    {
        return $this->kuesionerRepository->getPublishedByStatus($statusId);
    }

    public function getWithPertanyaan(int $kuesionerId)
    {
        return $this->kuesionerRepository->getKuesionerWithPertanyaan($kuesionerId);
    }

    public function getAlumniJawaban(int $kuesionerId, array $filters = [])
    {
        return $this->kuesionerRepository->getAlumniJawaban($kuesionerId, $filters);
    }

    public function getAlumniJawabanDetail(int $kuesionerId, int $alumniId)
    {
        return $this->kuesionerRepository->getAlumniJawabanDetail($kuesionerId, $alumniId);
    }

    public function updateKuesionerStatus(int $kuesionerId, string $status)
    {
        return $this->kuesionerRepository->updateKuesionerStatus($kuesionerId, $status);
    }

    public function createSectionQues(array $data)
    {
        return $this->sectionQuesRepository->create($data);
    }
}
