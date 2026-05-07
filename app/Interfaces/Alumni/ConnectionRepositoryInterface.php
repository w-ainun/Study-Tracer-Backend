<?php

namespace App\Interfaces\Alumni;

use App\Models\AlumniBlock;
use App\Models\AlumniConnection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ConnectionRepositoryInterface
{
    // =====================
    // CONNECTION METHODS
    // =====================

    /**
     * Kirim permintaan koneksi.
     */
    public function sendRequest(int $requesterId, int $addresseeId): AlumniConnection;

    /**
     * Terima permintaan koneksi.
     */
    public function acceptRequest(int $connectionId): AlumniConnection;

    /**
     * Tolak permintaan koneksi.
     */
    public function rejectRequest(int $connectionId): AlumniConnection;

    /**
     * Hapus koneksi atau batalkan permintaan.
     */
    public function removeConnection(int $connectionId): bool;

    /**
     * Cari record koneksi antara dua alumni (dari arah manapun).
     */
    public function findConnectionBetween(int $alumniIdA, int $alumniIdB): ?AlumniConnection;

    /**
     * Dapatkan status koneksi dan block secara bulk untuk sekelompok alumni.
     */
    public function getBatchConnectionStatus(int $alumniId, array $targetAlumniIds): array;

    /**
     * Cari record koneksi berdasarkan ID.
     */
    public function findConnectionById(int $connectionId): ?AlumniConnection;

    /**
     * Get daftar koneksi (accepted) seorang alumni.
     */
    public function getConnections(int $alumniId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get permintaan masuk (pending, saya sebagai addressee).
     */
    public function getPendingRequests(int $alumniId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get permintaan terkirim (pending, saya sebagai requester).
     */
    public function getSentRequests(int $alumniId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Hitung jumlah koneksi (accepted).
     */
    public function getConnectionsCount(int $alumniId): int;

    /**
     * Hitung jumlah permintaan masuk (pending).
     */
    public function getPendingRequestsCount(int $alumniId): int;

    /**
     * Get mutual connections antara dua alumni.
     */
    public function getMutualConnections(int $alumniIdA, int $alumniIdB, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get saran koneksi berdasarkan jurusan, tahun lulus, skills.
     */
    public function getSuggestedAlumni(int $alumniId, int $limit = 10): Collection;

    // =====================
    // BLOCK METHODS
    // =====================

    /**
     * Block alumni.
     */
    public function blockAlumni(int $blockerId, int $blockedId): AlumniBlock;

    /**
     * Unblock alumni.
     */
    public function unblockAlumni(int $blockerId, int $blockedId): bool;

    /**
     * Cek apakah ada block antara dua alumni (dari arah manapun).
     */
    public function isBlocked(int $alumniIdA, int $alumniIdB): bool;

    /**
     * Cek apakah alumni A memblock alumni B (satu arah spesifik).
     */
    public function hasBlocked(int $blockerId, int $blockedId): bool;

    /**
     * Get daftar alumni yang di-block.
     */
    public function getBlockedAlumni(int $alumniId, int $perPage = 15): LengthAwarePaginator;
}
