<?php

namespace App\Interfaces;

interface KuesionerRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 15);
    public function getById(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function addPertanyaan(int $kuesionerId, array $data);
    public function updatePertanyaan(int $pertanyaanId, array $data);
    public function deletePertanyaan(int $pertanyaanId);
    public function addOpsiJawaban(int $pertanyaanId, array $opsiList);
    public function submitJawaban(int $userId, array $jawabanData);
    public function getPublished(int $perPage = 15);
    public function getKuesionerWithPertanyaan(int $kuesionerId);

    // Admin jawaban
    public function getAlumniJawaban(int $kuesionerId, array $filters = []);
    public function getAlumniJawabanDetail(int $kuesionerId, int $alumniId);
    public function updatePertanyaanStatus(int $pertanyaanId, string $status);
}
