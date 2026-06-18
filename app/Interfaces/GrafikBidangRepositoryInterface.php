<?php

namespace App\Interfaces;

interface GrafikBidangRepositoryInterface
{
    /**
     * Get overall kesesuaian bidang statistics (sesuai vs tidak sesuai).
     */
    public function getKesesuaianStats(array $filters = []): array;

    /**
     * Get kesesuaian breakdown per jurusan (for bar chart).
     */
    public function getKesesuaianByJurusan(array $filters = []): array;

    /**
     * Get kesesuaian breakdown per tahun lulus (for line chart).
     */
    public function getKesesuaianByTahunLulus(array $filters = []): array;

    /**
     * Get detailed alumni list with kesesuaian status (paginated).
     */
    public function getKesesuaianDetail(array $filters = [], int $perPage = 15);
}
