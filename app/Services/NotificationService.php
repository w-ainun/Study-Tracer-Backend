<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Membuat notifikasi baru
     */
    public function create(int $userId, string $type, string $title, string $message, ?array $data = null)
    {
        return Notification::create([
            'id_users' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Get notifikasi user dengan pagination
     */
    public function getUserNotifications(int $userId, ?bool $unreadOnly = null, int $perPage = 20)
    {
        $query = Notification::forUser($userId)
            ->orderBy('created_at', 'desc');

        if ($unreadOnly === true) {
            $query->unread();
        }

        return $query->paginate($perPage);
    }

    /**
     * Tandai notifikasi sebagai dibaca
     */
    public function markAsRead(int $notificationId, int $userId)
    {
        $notification = Notification::forUser($userId)->findOrFail($notificationId);
        $notification->markAsRead();
        return $notification;
    }

    /**
     * Tandai semua notifikasi user sebagai dibaca
     */
    public function markAllAsRead(int $userId)
    {
        return Notification::forUser($userId)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Hapus notifikasi
     */
    public function delete(int $notificationId, int $userId)
    {
        $notification = Notification::forUser($userId)->findOrFail($notificationId);
        return $notification->delete();
    }

    /**
     * Hapus semua notifikasi user
     */
    public function deleteAll(int $userId)
    {
        return Notification::forUser($userId)->delete();
    }

    /**
     * Hitung jumlah notifikasi belum dibaca
     */
    public function getUnreadCount(int $userId)
    {
        return Notification::forUser($userId)->unread()->count();
    }

    // ========================================
    // NOTIFIKASI SPESIFIK UNTUK EVENT TERTENTU
    // ========================================

    /**
     * Notifikasi saat akun alumni diverifikasi
     */
    public function notifyAccountVerified(int $userId)
    {
        return $this->create(
            $userId,
            'verification',
            'Akun Terverifikasi',
            'Selamat! Akun alumni Anda telah berhasil diverifikasi oleh admin. Sekarang Anda dapat mengakses semua fitur bursa kerja dan jejaring alumni.'
        );
    }

    /**
     * Notifikasi saat akun alumni ditolak
     */
    public function notifyAccountRejected(int $userId, ?string $reason = null)
    {
        $message = 'Mohon maaf, akun Anda ditolak oleh admin.';
        if ($reason) {
            $message .= ' Alasan: ' . $reason;
        }
        $message .= ' Anda dapat mendaftar ulang dengan data yang valid.';

        return $this->create(
            $userId,
            'verification',
            'Akun Ditolak',
            $message,
            ['reason' => $reason]
        );
    }

    /**
     * Notifikasi saat akun alumni dibanned
     */
    public function notifyAccountBanned(int $userId, ?string $reason = null)
    {
        $message = 'Akun Anda telah dibanned oleh admin dan tidak dapat digunakan lagi.';
        if ($reason) {
            $message .= ' Alasan: ' . $reason;
        }

        return $this->create(
            $userId,
            'verification',
            'Akun Dibanned',
            $message,
            ['reason' => $reason]
        );
    }

    /**
     * Notifikasi saat lowongan disetujui
     */
    public function notifyLowonganApproved(int $userId, int $lowonganId, string $jobTitle)
    {
        return $this->create(
            $userId,
            'lowongan',
            'Lowongan Disetujui',
            "Selamat! Lowongan kerja \"{$jobTitle}\" yang Anda kirimkan telah disetujui oleh admin dan sekarang dapat dilihat oleh alumni lain.",
            ['lowongan_id' => $lowonganId, 'job_title' => $jobTitle]
        );
    }

    /**
     * Notifikasi saat lowongan ditolak
     */
    public function notifyLowonganRejected(int $userId, int $lowonganId, string $jobTitle, ?string $reason = null)
    {
        $message = "Lowongan kerja \"{$jobTitle}\" yang Anda kirimkan ditolak oleh admin.";
        if ($reason) {
            $message .= ' Alasan: ' . $reason;
        }

        return $this->create(
            $userId,
            'lowongan',
            'Lowongan Ditolak',
            $message,
            ['lowongan_id' => $lowonganId, 'job_title' => $jobTitle, 'reason' => $reason]
        );
    }

    /**
     * Notifikasi saat status karir disetujui
     */
    public function notifyCareerStatusApproved(int $userId, int $riwayatId, string $statusName)
    {
        return $this->create(
            $userId,
            'career_status',
            'Status Karir Disetujui',
            "Status karir Anda \"{$statusName}\" telah disetujui oleh admin dan sekarang terlihat di profil Anda.",
            ['riwayat_id' => $riwayatId, 'status_name' => $statusName]
        );
    }

    /**
     * Notifikasi saat status karir ditolak
     */
    public function notifyCareerStatusRejected(int $userId, int $riwayatId, string $statusName, ?string $reason = null)
    {
        $message = "Status karir Anda \"{$statusName}\" ditolak oleh admin.";
        if ($reason) {
            $message .= ' Alasan: ' . $reason;
        }

        return $this->create(
            $userId,
            'career_status',
            'Status Karir Ditolak',
            $message,
            ['riwayat_id' => $riwayatId, 'status_name' => $statusName, 'reason' => $reason]
        );
    }

    /**
     * Notifikasi saat ada kuesioner baru sesuai status karir
     */
    public function notifyNewKuesioner(int $userId, int $kuesionerId, string $title, string $statusName)
    {
        return $this->create(
            $userId,
            'kuesioner',
            'Kuesioner Baru Tersedia',
            "Ada kuesioner baru \"{$title}\" untuk alumni dengan status {$statusName}. Mohon luangkan waktu Anda untuk mengisi kuesioner ini.",
            ['kuesioner_id' => $kuesionerId, 'kuesioner_title' => $title, 'status_name' => $statusName]
        );
    }
}
