<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all notifications untuk user yang login
     */
    public function index(Request $request)
    {
        try {
            $userId = $request->user()->id_users;
            $unreadOnly = $request->query('unread_only') === 'true';
            $perPage = $request->query('per_page', 20);

            $notifications = $this->notificationService->getUserNotifications(
                $userId,
                $unreadOnly,
                $perPage
            );

            return $this->successResponse($notifications);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil notifikasi: ' . $e->getMessage());
        }
    }

    /**
     * Get jumlah notifikasi belum dibaca
     */
    public function unreadCount(Request $request)
    {
        try {
            $userId = $request->user()->id_users;
            $count = $this->notificationService->getUnreadCount($userId);

            return $this->successResponse(['unread_count' => $count]);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghitung notifikasi');
        }
    }

    /**
     * Tandai notifikasi sebagai dibaca
     */
    public function markAsRead(Request $request, $id)
    {
        try {
            $userId = $request->user()->id_users;
            $notification = $this->notificationService->markAsRead($id, $userId);

            return $this->successResponse($notification, 'Notifikasi ditandai sebagai dibaca');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menandai notifikasi');
        }
    }

    /**
     * Tandai semua notifikasi sebagai dibaca
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $userId = $request->user()->id_users;
            $count = $this->notificationService->markAllAsRead($userId);

            return $this->successResponse(
                ['updated_count' => $count],
                'Semua notifikasi ditandai sebagai dibaca'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menandai semua notifikasi');
        }
    }

    /**
     * Hapus notifikasi
     */
    public function destroy(Request $request, $id)
    {
        try {
            $userId = $request->user()->id_users;
            $this->notificationService->delete($id, $userId);

            return $this->successResponse(null, 'Notifikasi berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus notifikasi');
        }
    }

    /**
     * Hapus semua notifikasi
     */
    public function destroyAll(Request $request)
    {
        try {
            $userId = $request->user()->id_users;
            $count = $this->notificationService->deleteAll($userId);

            return $this->successResponse(
                ['deleted_count' => $count],
                'Semua notifikasi berhasil dihapus'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus semua notifikasi');
        }
    }
}
