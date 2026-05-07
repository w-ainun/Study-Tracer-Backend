<?php

namespace App\Services\Alumni;

use App\Interfaces\Alumni\ConnectionRepositoryInterface;
use App\Models\Alumni;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class ConnectionService
{
    private ConnectionRepositoryInterface $connectionRepository;
    private NotificationService $notificationService;

    public function __construct(
        ConnectionRepositoryInterface $connectionRepository,
        NotificationService $notificationService
    ) {
        $this->connectionRepository = $connectionRepository;
        $this->notificationService = $notificationService;
    }

    // =====================
    // CONNECTION METHODS
    // =====================

    /**
     * Kirim permintaan koneksi.
     *
     * @throws \Exception
     */
    public function sendConnectionRequest(int $userId, int $targetAlumniId)
    {
        $alumni = $this->getAlumniByUserId($userId);
        $targetAlumni = Alumni::where('id_alumni', $targetAlumniId)
            ->where('status_create', 'ok')
            ->first();

        if (!$targetAlumni) {
            throw new \Exception('Alumni tidak ditemukan atau belum terverifikasi.');
        }

        // Tidak bisa connect dengan diri sendiri
        if ($alumni->id_alumni === $targetAlumniId) {
            throw new \Exception('Tidak dapat mengirim permintaan koneksi ke diri sendiri.');
        }

        // Cek apakah ada block
        if ($this->connectionRepository->isBlocked($alumni->id_alumni, $targetAlumniId)) {
            throw new \Exception('Tidak dapat mengirim permintaan koneksi.');
        }

        // Cek apakah sudah ada koneksi
        $existing = $this->connectionRepository->findConnectionBetween($alumni->id_alumni, $targetAlumniId);

        if ($existing) {
            if ($existing->status === 'accepted') {
                throw new \Exception('Sudah terkoneksi dengan alumni ini.');
            }
            if ($existing->status === 'pending') {
                // Jika saya yang menerima request pending dari orang ini, langsung accept
                if ($existing->id_alumni_addressee === $alumni->id_alumni) {
                    return $this->acceptConnectionRequest($userId, $existing->id_connection);
                }
                throw new \Exception('Permintaan koneksi sudah dikirim sebelumnya. Menunggu konfirmasi.');
            }
            if ($existing->status === 'rejected') {
                // Jika sebelumnya di-reject, hapus dulu lalu buat fresh request
                $this->connectionRepository->removeConnection($existing->id_connection);
            }
        }

        $connection = $this->connectionRepository->sendRequest($alumni->id_alumni, $targetAlumniId);

        // Kirim notifikasi ke target
        $this->notificationService->notifyConnectionRequest(
            $targetAlumni->user->id_users,
            $alumni->id_alumni,
            $alumni->nama_alumni
        );

        return $connection->load(['requester', 'addressee']);
    }

    /**
     * Terima permintaan koneksi.
     *
     * @throws \Exception
     */
    public function acceptConnectionRequest(int $userId, int $connectionId)
    {
        $alumni = $this->getAlumniByUserId($userId);

        $connection = $this->connectionRepository->findConnectionById($connectionId);

        if (!$connection) {
            throw new \Exception('Permintaan koneksi tidak ditemukan.');
        }

        // Hanya addressee yang bisa menerima
        if ($connection->id_alumni_addressee !== $alumni->id_alumni) {
            throw new \Exception('Anda tidak berhak menerima permintaan ini.');
        }

        if ($connection->status !== 'pending') {
            throw new \Exception('Permintaan koneksi sudah diproses sebelumnya.');
        }

        $connection = $this->connectionRepository->acceptRequest($connectionId);

        // Kirim notifikasi ke requester bahwa koneksi diterima
        $requester = $connection->requester;
        $this->notificationService->notifyConnectionAccepted(
            $requester->user->id_users,
            $alumni->id_alumni,
            $alumni->nama_alumni
        );

        return $connection;
    }

    /**
     * Tolak permintaan koneksi.
     *
     * @throws \Exception
     */
    public function rejectConnectionRequest(int $userId, int $connectionId)
    {
        $alumni = $this->getAlumniByUserId($userId);

        $connection = $this->connectionRepository->findConnectionById($connectionId);

        if (!$connection) {
            throw new \Exception('Permintaan koneksi tidak ditemukan.');
        }

        // Hanya addressee yang bisa menolak
        if ($connection->id_alumni_addressee !== $alumni->id_alumni) {
            throw new \Exception('Anda tidak berhak menolak permintaan ini.');
        }

        if ($connection->status !== 'pending') {
            throw new \Exception('Permintaan koneksi sudah diproses sebelumnya.');
        }

        return $this->connectionRepository->rejectRequest($connectionId);
    }

    /**
     * Hapus koneksi atau batalkan permintaan yang sudah dikirim.
     *
     * @throws \Exception
     */
    public function removeConnection(int $userId, int $targetAlumniId)
    {
        $alumni = $this->getAlumniByUserId($userId);

        $connection = $this->connectionRepository->findConnectionBetween($alumni->id_alumni, $targetAlumniId);

        if (!$connection) {
            throw new \Exception('Koneksi tidak ditemukan.');
        }

        // Validasi: hanya pihak yang terlibat yang bisa menghapus
        if ($connection->id_alumni_requester !== $alumni->id_alumni
            && $connection->id_alumni_addressee !== $alumni->id_alumni) {
            throw new \Exception('Anda tidak berhak menghapus koneksi ini.');
        }

        return $this->connectionRepository->removeConnection($connection->id_connection);
    }

    /**
     * Get daftar koneksi saya (accepted).
     */
    public function getMyConnections(int $userId, int $perPage = 15)
    {
        $alumni = $this->getAlumniByUserId($userId);
        return $this->connectionRepository->getConnections($alumni->id_alumni, $perPage);
    }

    /**
     * Get permintaan masuk (pending).
     */
    public function getPendingRequests(int $userId, int $perPage = 15)
    {
        $alumni = $this->getAlumniByUserId($userId);
        return $this->connectionRepository->getPendingRequests($alumni->id_alumni, $perPage);
    }

    /**
     * Get permintaan terkirim (pending).
     */
    public function getSentRequests(int $userId, int $perPage = 15)
    {
        $alumni = $this->getAlumniByUserId($userId);
        return $this->connectionRepository->getSentRequests($alumni->id_alumni, $perPage);
    }

    /**
     * Get koneksi alumni lain (accepted).
     */
    public function getAlumniConnections(int $targetAlumniId, int $perPage = 15)
    {
        $target = Alumni::where('id_alumni', $targetAlumniId)->where('status_create', 'ok')->firstOrFail();
        return $this->connectionRepository->getConnections($target->id_alumni, $perPage);
    }

    /**
     * Get statistik koneksi saya.
     */
    public function getMyConnectionStats(int $userId): array
    {
        $alumni = $this->getAlumniByUserId($userId);

        return [
            'connections_count' => $this->connectionRepository->getConnectionsCount($alumni->id_alumni),
            'pending_requests_count' => $this->connectionRepository->getPendingRequestsCount($alumni->id_alumni),
        ];
    }

    /**
     * Get statistik koneksi alumni lain.
     */
    public function getAlumniConnectionStats(int $targetAlumniId): array
    {
        return [
            'connections_count' => $this->connectionRepository->getConnectionsCount($targetAlumniId),
        ];
    }

    /**
     * Cek status koneksi antara saya dan alumni lain.
     */
    public function getConnectionStatus(int $userId, int $targetAlumniId): array
    {
        $alumni = $this->getAlumniByUserId($userId);

        $connection = $this->connectionRepository->findConnectionBetween($alumni->id_alumni, $targetAlumniId);

        if (!$connection) {
            // Cek apakah ada block
            $isBlocked = $this->connectionRepository->isBlocked($alumni->id_alumni, $targetAlumniId);
            return [
                'status' => $isBlocked ? 'blocked' : 'none',
                'connection_id' => null,
                'direction' => null,
            ];
        }

        $direction = $connection->id_alumni_requester === $alumni->id_alumni ? 'sent' : 'received';

        return [
            'status' => $connection->status,
            'connection_id' => $connection->id_connection,
            'direction' => $direction,
            'created_at' => $connection->created_at,
            'accepted_at' => $connection->accepted_at,
        ];
    }

    /**
     * Dapatkan status koneksi secara bulk.
     */
    public function getBatchConnectionStatus(int $userId, array $targetAlumniIds): array
    {
        $alumni = $this->getAlumniByUserId($userId);

        $batchData = $this->connectionRepository->getBatchConnectionStatus($alumni->id_alumni, $targetAlumniIds);
        
        $results = [];
        
        foreach ($batchData as $targetId => $data) {
            $connection = $data['connection'];
            $block = $data['block'];
            
            if (!$connection) {
                $isBlocked = $block !== null;
                $results[$targetId] = [
                    'status' => $isBlocked ? 'blocked' : 'none',
                    'connection_id' => null,
                    'direction' => null,
                ];
                continue;
            }
            
            $direction = $connection->id_alumni_requester === $alumni->id_alumni ? 'sent' : 'received';
            
            $results[$targetId] = [
                'status' => $connection->status,
                'connection_id' => $connection->id_connection,
                'direction' => $direction,
                'created_at' => $connection->created_at,
                'accepted_at' => $connection->accepted_at,
            ];
        }
        
        return $results;
    }

    /**
     * Get mutual connections antara saya dan alumni lain.
     */
    public function getMutualConnections(int $userId, int $targetAlumniId, int $perPage = 15)
    {
        $alumni = $this->getAlumniByUserId($userId);
        return $this->connectionRepository->getMutualConnections($alumni->id_alumni, $targetAlumniId, $perPage);
    }

    /**
     * Get saran koneksi.
     */
    public function getSuggestions(int $userId, int $limit = 10)
    {
        $alumni = $this->getAlumniByUserId($userId);
        return $this->connectionRepository->getSuggestedAlumni($alumni->id_alumni, $limit);
    }

    // =====================
    // BLOCK METHODS
    // =====================

    /**
     * Block alumni. Juga otomatis hapus koneksi yang ada.
     *
     * @throws \Exception
     */
    public function blockAlumni(int $userId, int $targetAlumniId)
    {
        $alumni = $this->getAlumniByUserId($userId);

        if ($alumni->id_alumni === $targetAlumniId) {
            throw new \Exception('Tidak dapat memblock diri sendiri.');
        }

        $targetAlumni = Alumni::findOrFail($targetAlumniId);

        // Cek apakah sudah di-block
        if ($this->connectionRepository->hasBlocked($alumni->id_alumni, $targetAlumniId)) {
            throw new \Exception('Alumni sudah di-block sebelumnya.');
        }

        return DB::transaction(function () use ($alumni, $targetAlumniId) {
            // Hapus koneksi yang ada (jika ada)
            $connection = $this->connectionRepository->findConnectionBetween($alumni->id_alumni, $targetAlumniId);
            if ($connection) {
                $this->connectionRepository->removeConnection($connection->id_connection);
            }

            // Block
            return $this->connectionRepository->blockAlumni($alumni->id_alumni, $targetAlumniId);
        });
    }

    /**
     * Unblock alumni.
     *
     * @throws \Exception
     */
    public function unblockAlumni(int $userId, int $targetAlumniId)
    {
        $alumni = $this->getAlumniByUserId($userId);

        if (!$this->connectionRepository->hasBlocked($alumni->id_alumni, $targetAlumniId)) {
            throw new \Exception('Alumni tidak sedang di-block.');
        }

        return $this->connectionRepository->unblockAlumni($alumni->id_alumni, $targetAlumniId);
    }

    /**
     * Get daftar alumni yang di-block.
     */
    public function getBlockedAlumni(int $userId, int $perPage = 15)
    {
        $alumni = $this->getAlumniByUserId($userId);
        return $this->connectionRepository->getBlockedAlumni($alumni->id_alumni, $perPage);
    }

    // =====================
    // PRIVATE HELPERS
    // =====================

    /**
     * Get alumni dari user ID.
     *
     * @throws \Exception
     */
    private function getAlumniByUserId(int $userId): Alumni
    {
        $alumni = Alumni::where('id_users', $userId)->first();

        if (!$alumni) {
            throw new \Exception('Profil alumni tidak ditemukan.');
        }

        return $alumni;
    }
}
