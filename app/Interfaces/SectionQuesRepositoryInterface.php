<?php

namespace App\Interfaces;

interface SectionQuesRepositoryInterface
{
    public function getAll(int $kuesionerId);
    public function getById(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function addPertanyaan(int $sectionId, array $data);
}
