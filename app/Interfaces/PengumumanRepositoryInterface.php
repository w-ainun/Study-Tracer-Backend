<?php

namespace App\Interfaces;

interface PengumumanRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 15);
    public function getById(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function togglePin(int $id);
    public function getStatusCounts(): array;
}
