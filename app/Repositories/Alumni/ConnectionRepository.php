<?php

namespace App\Repositories\Alumni;

use App\Interfaces\Alumni\ConnectionRepositoryInterface;
use App\Models\Alumni;
use App\Models\AlumniBlock;
use App\Models\AlumniConnection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ConnectionRepository implements ConnectionRepositoryInterface
{
    // =====================
    // CONNECTION METHODS
    // =====================

    /**
     * Kirim permintaan koneksi.
     */
    public function sendRequest(int $requesterId, int $addresseeId): AlumniConnection
    {
        return AlumniConnection::create([
            'id_alumni_requester' => $requesterId,
            'id_alumni_addressee' => $addresseeId,
            'status' => 'pending',
        ]);
    }

    /**
     * Terima permintaan koneksi.
     */
    public function acceptRequest(int $connectionId): AlumniConnection
    {
        $connection = AlumniConnection::findOrFail($connectionId);
        $connection->accept();
        return $connection->fresh(['requester', 'addressee']);
    }

    /**
     * Tolak permintaan koneksi.
     */
    public function rejectRequest(int $connectionId): AlumniConnection
    {
        $connection = AlumniConnection::findOrFail($connectionId);
        $connection->reject();
        return $connection->fresh(['requester', 'addressee']);
    }

    /**
     * Hapus koneksi atau batalkan permintaan.
     */
    public function removeConnection(int $connectionId): bool
    {
        return AlumniConnection::where('id_connection', $connectionId)->delete() > 0;
    }

    /**
     * Cari record koneksi antara dua alumni (dari arah manapun).
     */
    public function findConnectionBetween(int $alumniIdA, int $alumniIdB): ?AlumniConnection
    {
        return AlumniConnection::where(function ($q) use ($alumniIdA, $alumniIdB) {
            $q->where(function ($inner) use ($alumniIdA, $alumniIdB) {
                $inner->where('id_alumni_requester', $alumniIdA)
                      ->where('id_alumni_addressee', $alumniIdB);
            })->orWhere(function ($inner) use ($alumniIdA, $alumniIdB) {
                $inner->where('id_alumni_requester', $alumniIdB)
                      ->where('id_alumni_addressee', $alumniIdA);
            });
        })->first();
    }

    /**
     * Dapatkan status koneksi dan block secara bulk untuk sekelompok alumni.
     */
    public function getBatchConnectionStatus(int $alumniId, array $targetAlumniIds): array
    {
        if (empty($targetAlumniIds)) return [];

        // 1. Dapatkan semua koneksi yang melibatkan alumniId & targetAlumniIds
        $connections = AlumniConnection::where(function ($q) use ($alumniId, $targetAlumniIds) {
            $q->where('id_alumni_requester', $alumniId)
              ->whereIn('id_alumni_addressee', $targetAlumniIds);
        })->orWhere(function ($q) use ($alumniId, $targetAlumniIds) {
            $q->where('id_alumni_addressee', $alumniId)
              ->whereIn('id_alumni_requester', $targetAlumniIds);
        })->get();

        // 2. Dapatkan semua blocks yang melibatkan alumniId & targetAlumniIds
        $blocks = AlumniBlock::where(function ($q) use ($alumniId, $targetAlumniIds) {
            $q->where('id_alumni_blocker', $alumniId)
              ->whereIn('id_alumni_blocked', $targetAlumniIds);
        })->orWhere(function ($q) use ($alumniId, $targetAlumniIds) {
            $q->where('id_alumni_blocked', $alumniId)
              ->whereIn('id_alumni_blocker', $targetAlumniIds);
        })->get();

        $result = [];

        foreach ($targetAlumniIds as $targetId) {
            // Find connection
            $conn = $connections->first(function ($c) use ($alumniId, $targetId) {
                return ($c->id_alumni_requester === $alumniId && $c->id_alumni_addressee === $targetId) ||
                       ($c->id_alumni_addressee === $alumniId && $c->id_alumni_requester === $targetId);
            });

            // Find block
            $block = $blocks->first(function ($b) use ($alumniId, $targetId) {
                return ($b->id_alumni_blocker === $alumniId && $b->id_alumni_blocked === $targetId) ||
                       ($b->id_alumni_blocked === $alumniId && $b->id_alumni_blocker === $targetId);
            });

            $result[$targetId] = [
                'connection' => $conn,
                'block' => $block
            ];
        }

        return $result;
    }

    /**
     * Cari record koneksi berdasarkan ID.
     */
    public function findConnectionById(int $connectionId): ?AlumniConnection
    {
        return AlumniConnection::with(['requester', 'addressee'])->find($connectionId);
    }

    /**
     * Get daftar koneksi (accepted) seorang alumni — gabungan dari requester & addressee.
     */
    public function getConnections(int $alumniId, int $perPage = 15): LengthAwarePaginator
    {
        // Ambil ID semua alumni yang terkoneksi
        $connectedIds = $this->getConnectedAlumniIds($alumniId);

        return Alumni::with([
                'jurusan',
                'riwayatStatus' => fn($q) => $q->where('approval_status', 'approved')->latest('id_riwayat')->limit(1),
                'riwayatStatus.status',
                'riwayatStatus.pekerjaan.perusahaan',
                'riwayatStatus.kuliah.universitas',
                'riwayatStatus.wirausaha',
            ])
            ->whereIn('id_alumni', $connectedIds)
            ->orderByDesc('updated_at')
            ->paginate($perPage);
    }

    /**
     * Get permintaan masuk (pending, saya sebagai addressee).
     */
    public function getPendingRequests(int $alumniId, int $perPage = 15): LengthAwarePaginator
    {
        return AlumniConnection::with([
                'requester',
                'requester.jurusan',
                'requester.riwayatStatus' => fn($q) => $q->where('approval_status', 'approved')->latest('id_riwayat')->limit(1),
                'requester.riwayatStatus.status',
                'requester.riwayatStatus.pekerjaan.perusahaan',
                'requester.riwayatStatus.kuliah.universitas',
                'requester.riwayatStatus.wirausaha',
            ])
            ->where('id_alumni_addressee', $alumniId)
            ->pending()
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Get permintaan terkirim (pending, saya sebagai requester).
     */
    public function getSentRequests(int $alumniId, int $perPage = 15): LengthAwarePaginator
    {
        return AlumniConnection::with([
                'addressee',
                'addressee.jurusan',
                'addressee.riwayatStatus' => fn($q) => $q->where('approval_status', 'approved')->latest('id_riwayat')->limit(1),
                'addressee.riwayatStatus.status',
                'addressee.riwayatStatus.pekerjaan.perusahaan',
                'addressee.riwayatStatus.kuliah.universitas',
                'addressee.riwayatStatus.wirausaha',
            ])
            ->where('id_alumni_requester', $alumniId)
            ->pending()
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Hitung jumlah koneksi (accepted).
     */
    public function getConnectionsCount(int $alumniId): int
    {
        return AlumniConnection::accepted()
            ->involvingAlumni($alumniId)
            ->count();
    }

    /**
     * Hitung jumlah permintaan masuk (pending).
     */
    public function getPendingRequestsCount(int $alumniId): int
    {
        return AlumniConnection::pending()
            ->where('id_alumni_addressee', $alumniId)
            ->count();
    }

    /**
     * Get mutual connections antara dua alumni.
     * Mutual connections = alumni yang terkoneksi dengan keduanya.
     */
    public function getMutualConnections(int $alumniIdA, int $alumniIdB, int $perPage = 15): LengthAwarePaginator
    {
        $connectedIdsA = $this->getConnectedAlumniIds($alumniIdA);
        $connectedIdsB = $this->getConnectedAlumniIds($alumniIdB);

        $mutualIds = array_intersect($connectedIdsA, $connectedIdsB);

        return Alumni::with([
                'jurusan',
                'riwayatStatus' => fn($q) => $q->where('approval_status', 'approved')->latest('id_riwayat')->limit(1),
                'riwayatStatus.status',
                'riwayatStatus.pekerjaan.perusahaan',
                'riwayatStatus.kuliah.universitas',
                'riwayatStatus.wirausaha',
            ])
            ->whereIn('id_alumni', $mutualIds)
            ->paginate($perPage);
    }

    /**
     * Get saran koneksi berdasarkan jurusan, tahun lulus, skills yang sama.
     * Exclude: sudah connected, sudah ada pending request, sudah di-block.
     */
    public function getSuggestedAlumni(int $alumniId, int $limit = 10): Collection
    {
        $alumni = Alumni::findOrFail($alumniId);

        // IDs to exclude: diri sendiri + sudah terkoneksi + ada pending + blocked
        $excludeIds = $this->getExcludedAlumniIds($alumniId);
        $excludeIds[] = $alumniId;

        // Retrieve alumni skill IDs for matching
        $mySkillIds = $alumni->skills()->pluck('skills.id_skills')->toArray();

        $query = Alumni::with([
                'jurusan',
                'riwayatStatus' => fn($q) => $q->where('approval_status', 'approved')->latest('id_riwayat')->limit(1),
                'riwayatStatus.status',
                'riwayatStatus.pekerjaan.perusahaan',
                'riwayatStatus.kuliah.universitas',
                'riwayatStatus.wirausaha',
            ])
            ->where('status_create', 'ok')
            ->whereNotIn('id_alumni', $excludeIds);

        // Scoring: jurusan sama, tahun lulus dekat, skills overlap
        $query->selectRaw('alumni.*, (
            CASE WHEN id_jurusan = ? THEN 3 ELSE 0 END +
            CASE WHEN ABS(YEAR(tahun_lulus) - ?) <= 2 THEN 2 ELSE 0 END
        ) as relevance_score', [
            $alumni->id_jurusan,
            $alumni->tahun_lulus ? $alumni->tahun_lulus->year : 0,
        ]);

        // Boost by shared skills count
        if (!empty($mySkillIds)) {
            $placeholders = implode(',', array_fill(0, count($mySkillIds), '?'));
            $query->addSelect(DB::raw("(
                SELECT COUNT(*) FROM alumni_skills
                WHERE alumni_skills.id_alumni = alumni.id_alumni
                AND alumni_skills.id_skills IN ({$placeholders})
            ) as shared_skills_count"))
            ->addBinding($mySkillIds, 'select');
        } else {
            $query->addSelect(DB::raw('0 as shared_skills_count'));
        }

        return $query->orderByRaw('relevance_score + shared_skills_count DESC')
            ->limit($limit)
            ->get();
    }

    // =====================
    // BLOCK METHODS
    // =====================

    /**
     * Block alumni.
     */
    public function blockAlumni(int $blockerId, int $blockedId): AlumniBlock
    {
        return AlumniBlock::create([
            'id_alumni_blocker' => $blockerId,
            'id_alumni_blocked' => $blockedId,
        ]);
    }

    /**
     * Unblock alumni.
     */
    public function unblockAlumni(int $blockerId, int $blockedId): bool
    {
        return AlumniBlock::where('id_alumni_blocker', $blockerId)
            ->where('id_alumni_blocked', $blockedId)
            ->delete() > 0;
    }

    /**
     * Cek apakah ada block antara dua alumni (dari arah manapun).
     */
    public function isBlocked(int $alumniIdA, int $alumniIdB): bool
    {
        return AlumniBlock::between($alumniIdA, $alumniIdB)->exists();
    }

    /**
     * Cek apakah alumni A memblock alumni B secara spesifik.
     */
    public function hasBlocked(int $blockerId, int $blockedId): bool
    {
        return AlumniBlock::where('id_alumni_blocker', $blockerId)
            ->where('id_alumni_blocked', $blockedId)
            ->exists();
    }

    /**
     * Get daftar alumni yang di-block.
     */
    public function getBlockedAlumni(int $alumniId, int $perPage = 15): LengthAwarePaginator
    {
        return AlumniBlock::with([
                'blocked',
                'blocked.jurusan',
            ])
            ->byBlocker($alumniId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    // =====================
    // PRIVATE HELPERS
    // =====================

    /**
     * Get array of alumni IDs yang terkoneksi (accepted).
     */
    public function getConnectedAlumniIds(int $alumniId): array
    {
        $asRequester = AlumniConnection::accepted()
            ->where('id_alumni_requester', $alumniId)
            ->pluck('id_alumni_addressee')
            ->toArray();

        $asAddressee = AlumniConnection::accepted()
            ->where('id_alumni_addressee', $alumniId)
            ->pluck('id_alumni_requester')
            ->toArray();

        return array_unique(array_merge($asRequester, $asAddressee));
    }

    /**
     * Get IDs alumni yang di-exclude dari saran koneksi.
     * (sudah connected, ada pending request, atau ada block).
     */
    private function getExcludedAlumniIds(int $alumniId): array
    {
        // Semua alumni yang sudah ada di tabel connections (pending / accepted)
        $connectionIds = AlumniConnection::where('status', '!=', 'rejected')
            ->where(function ($q) use ($alumniId) {
                $q->where('id_alumni_requester', $alumniId)
                  ->orWhere('id_alumni_addressee', $alumniId);
            })
            ->get()
            ->flatMap(function ($conn) use ($alumniId) {
                return [
                    $conn->id_alumni_requester === $alumniId
                        ? $conn->id_alumni_addressee
                        : $conn->id_alumni_requester,
                ];
            })
            ->toArray();

        // Semua alumni yang terlibat block (dari arah manapun)
        $blockedIds = AlumniBlock::where('id_alumni_blocker', $alumniId)
            ->pluck('id_alumni_blocked')
            ->toArray();

        $blockedByIds = AlumniBlock::where('id_alumni_blocked', $alumniId)
            ->pluck('id_alumni_blocker')
            ->toArray();

        return array_unique(array_merge($connectionIds, $blockedIds, $blockedByIds));
    }
}
