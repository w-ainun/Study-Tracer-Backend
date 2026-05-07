<?php

namespace App\Http\Controllers\Api\Alumni;

use App\Http\Controllers\Controller;
use App\Http\Resources\Alumni\ConnectionResource;
use App\Http\Resources\Alumni\ConnectionStatsResource;
use App\Services\Alumni\ConnectionService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ConnectionController extends Controller
{
    use ApiResponse;

    private ConnectionService $connectionService;

    public function __construct(ConnectionService $connectionService)
    {
        $this->connectionService = $connectionService;
    }

    // =====================
    // CONNECTION ENDPOINTS
    // =====================

    /**
     * POST /alumni/connections/{id}/request
     * Kirim permintaan koneksi ke alumni lain.
     */
    public function sendRequest(int $id)
    {
        try {
            $userId = auth()->user()->id_users;
            $connection = $this->connectionService->sendConnectionRequest($userId, $id);

            return $this->createdResponse([
                'connection' => [
                    'id_connection' => $connection->id_connection,
                    'status' => $connection->status,
                    'created_at' => $connection->created_at,
                ],
            ], 'Permintaan koneksi berhasil dikirim.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * POST /alumni/connections/{id}/accept
     * Terima permintaan koneksi (id = id_connection).
     */
    public function acceptRequest(int $id)
    {
        try {
            $userId = auth()->user()->id_users;
            $connection = $this->connectionService->acceptConnectionRequest($userId, $id);

            return $this->successResponse([
                'connection' => [
                    'id_connection' => $connection->id_connection,
                    'status' => $connection->status,
                    'accepted_at' => $connection->accepted_at,
                ],
            ], 'Permintaan koneksi diterima.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * POST /alumni/connections/{id}/reject
     * Tolak permintaan koneksi (id = id_connection).
     */
    public function rejectRequest(int $id)
    {
        try {
            $userId = auth()->user()->id_users;
            $connection = $this->connectionService->rejectConnectionRequest($userId, $id);

            return $this->successResponse([
                'connection' => [
                    'id_connection' => $connection->id_connection,
                    'status' => $connection->status,
                ],
            ], 'Permintaan koneksi ditolak.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * DELETE /alumni/connections/{id}
     * Hapus koneksi atau batalkan permintaan (id = id_alumni target).
     */
    public function removeConnection(int $id)
    {
        try {
            $userId = auth()->user()->id_users;
            $this->connectionService->removeConnection($userId, $id);

            return $this->successResponse(null, 'Koneksi berhasil dihapus.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * GET /alumni/connections
     * List koneksi saya (accepted).
     */
    public function myConnections(Request $request)
    {
        try {
            $userId = auth()->user()->id_users;
            $perPage = $request->input('per_page', 15);
            $paginated = $this->connectionService->getMyConnections($userId, $perPage);

            return $this->successResponse([
                'data'         => ConnectionResource::collection($paginated),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data koneksi: ' . $e->getMessage());
        }
    }

    /**
     * GET /alumni/connections/pending
     * List permintaan masuk (pending).
     */
    public function pendingRequests(Request $request)
    {
        try {
            $userId = auth()->user()->id_users;
            $perPage = $request->input('per_page', 15);
            $paginated = $this->connectionService->getPendingRequests($userId, $perPage);

            // Format pending requests — tampilkan data requester
            $data = $paginated->getCollection()->map(function ($connection) {
                $requester = $connection->requester;
                return [
                    'id_connection' => $connection->id_connection,
                    'alumni' => new ConnectionResource($requester),
                    'requested_at' => $connection->created_at,
                ];
            });

            return $this->successResponse([
                'data'         => $data,
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil permintaan koneksi: ' . $e->getMessage());
        }
    }

    /**
     * GET /alumni/connections/sent
     * List permintaan terkirim (pending).
     */
    public function sentRequests(Request $request)
    {
        try {
            $userId = auth()->user()->id_users;
            $perPage = $request->input('per_page', 15);
            $paginated = $this->connectionService->getSentRequests($userId, $perPage);

            // Format sent requests — tampilkan data addressee
            $data = $paginated->getCollection()->map(function ($connection) {
                $addressee = $connection->addressee;
                return [
                    'id_connection' => $connection->id_connection,
                    'alumni' => new ConnectionResource($addressee),
                    'sent_at' => $connection->created_at,
                ];
            });

            return $this->successResponse([
                'data'         => $data,
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil permintaan terkirim: ' . $e->getMessage());
        }
    }

    /**
     * GET /alumni/connections/{id}/connections
     * List koneksi alumni tertentu (accepted).
     */
    public function alumniConnections(Request $request, int $id)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $paginated = $this->connectionService->getAlumniConnections($id, $perPage);

            return $this->successResponse([
                'data'         => ConnectionResource::collection($paginated),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Alumni tidak ditemukan.');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data koneksi: ' . $e->getMessage());
        }
    }

    /**
     * GET /alumni/connections/stats
     * Statistik koneksi saya.
     */
    public function myStats()
    {
        try {
            $userId = auth()->user()->id_users;
            $stats = $this->connectionService->getMyConnectionStats($userId);

            return $this->successResponse(new ConnectionStatsResource($stats));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil statistik koneksi: ' . $e->getMessage());
        }
    }

    /**
     * GET /alumni/connections/{id}/stats
     * Statistik koneksi alumni lain.
     */
    public function alumniStats(int $id)
    {
        try {
            $stats = $this->connectionService->getAlumniConnectionStats($id);

            return $this->successResponse(new ConnectionStatsResource($stats));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil statistik koneksi: ' . $e->getMessage());
        }
    }

    /**
     * GET /alumni/connections/{id}/status
     * Cek status koneksi antara saya dan alumni lain.
     */
    public function connectionStatus(int $id)
    {
        try {
            $userId = auth()->user()->id_users;
            $status = $this->connectionService->getConnectionStatus($userId, $id);

            return $this->successResponse($status);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengecek status koneksi: ' . $e->getMessage());
        }
    }

    /**
     * POST /alumni/connections/status-batch
     * Cek status koneksi secara batch.
     */
    public function batchConnectionStatus(Request $request)
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer'
            ]);

            $userId = auth()->user()->id_users;
            $statuses = $this->connectionService->getBatchConnectionStatus($userId, $request->ids);

            return $this->successResponse($statuses);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengecek status koneksi batch: ' . $e->getMessage());
        }
    }

    /**
     * GET /alumni/connections/mutual/{id}
     * Mutual connections antara saya dan alumni lain.
     */
    public function mutualConnections(Request $request, int $id)
    {
        try {
            $userId = auth()->user()->id_users;
            $perPage = $request->input('per_page', 15);
            $paginated = $this->connectionService->getMutualConnections($userId, $id, $perPage);

            return $this->successResponse([
                'data'         => ConnectionResource::collection($paginated),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil mutual connections: ' . $e->getMessage());
        }
    }

    /**
     * GET /alumni/connections/suggestions
     * Saran koneksi berdasarkan jurusan, tahun lulus, skills.
     */
    public function suggestions(Request $request)
    {
        try {
            $userId = auth()->user()->id_users;
            $limit = $request->input('limit', 10);
            $suggestions = $this->connectionService->getSuggestions($userId, $limit);

            return $this->successResponse(ConnectionResource::collection($suggestions));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil saran koneksi: ' . $e->getMessage());
        }
    }

    // =====================
    // BLOCK ENDPOINTS
    // =====================

    /**
     * POST /alumni/connections/{id}/block
     * Block alumni.
     */
    public function blockAlumni(int $id)
    {
        try {
            $userId = auth()->user()->id_users;
            $this->connectionService->blockAlumni($userId, $id);

            return $this->successResponse(null, 'Alumni berhasil di-block.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * DELETE /alumni/connections/{id}/block
     * Unblock alumni.
     */
    public function unblockAlumni(int $id)
    {
        try {
            $userId = auth()->user()->id_users;
            $this->connectionService->unblockAlumni($userId, $id);

            return $this->successResponse(null, 'Alumni berhasil di-unblock.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * GET /alumni/connections/blocked
     * List alumni yang di-block.
     */
    public function blockedList(Request $request)
    {
        try {
            $userId = auth()->user()->id_users;
            $perPage = $request->input('per_page', 15);
            $paginated = $this->connectionService->getBlockedAlumni($userId, $perPage);

            $data = $paginated->getCollection()->map(function ($block) {
                return [
                    'id_block' => $block->id_block,
                    'alumni' => new ConnectionResource($block->blocked),
                    'blocked_at' => $block->created_at,
                ];
            });

            return $this->successResponse([
                'data'         => $data,
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil daftar block: ' . $e->getMessage());
        }
    }
}
