<?php

namespace App\Interfaces;

interface KuesionerRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 15);
    public function getById(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);

    // Pertanyaan
    public function getAllPertanyaan(array $filters = [], int $perPage = 15);
    public function addPertanyaan(int $kuesionerId, array $data);
    public function updatePertanyaan(int $pertanyaanId, array $data);
    public function deletePertanyaan(int $pertanyaanId);
    public function addOpsiJawaban(int $pertanyaanId, array $opsiList);

    // Jawaban
    public function submitJawaban(int $userId, array $jawabanData);

    // Published / Alumni
    public function getPublished(int $perPage = 15);
    public function getPublishedByStatus(int $statusId);
    public function getKuesionerWithPertanyaan(int $kuesionerId);

    // Admin Jawaban
    public function getAlumniJawaban(int $kuesionerId, array $filters = []);
    public function getAlumniJawabanDetail(int $kuesionerId, int $alumniId);

    // Status management
    public function updateKuesionerStatus(int $kuesionerId, string $status);
}
