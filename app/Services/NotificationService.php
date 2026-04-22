<?php

namespace App\Services;

use App\Events\NotificationReceived;
use App\Models\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Membuat notifikasi baru
     */
    public function create(int $userId, string $type, string $title, string $message, ?array $data = null)
    {
        $notification = Notification::create([
            'id_users' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
        
        // Clear cache setelah create notifikasi baru
        $this->clearNotificationCache($userId);

        // Broadcast real-time event via Reverb
        broadcast(new NotificationReceived($userId, [
            'id' => $notification->id_notification,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'created_at' => $notification->created_at->toISOString(),
        ]))->toOthers();
        
        return $notification;
    }

    /**
     * Get notifikasi user dengan pagination
     */
    public function getUserNotifications(int $userId, ?bool $unreadOnly = null, int $perPage = 20)
    {
        $query = Notification::forUser($userId)
            ->select(['id_notification', 'type', 'title', 'message', 'data', 'is_read', 'read_at', 'created_at'])
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
        
        // Clear cache setelah mark as read
        $this->clearNotificationCache($userId);
        
        return $notification;
    }

    /**
     * Tandai semua notifikasi user sebagai dibaca
     */
    public function markAllAsRead(int $userId)
    {
        $count = Notification::forUser($userId)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        
        // Clear cache setelah mark all as read
        $this->clearNotificationCache($userId);
        
        return $count;
    }

    /**
     * Hapus notifikasi
     */
    public function delete(int $notificationId, int $userId)
    {
        $notification = Notification::forUser($userId)->findOrFail($notificationId);
        $deleted = $notification->delete();
        
        // Clear cache setelah delete
        $this->clearNotificationCache($userId);
        
        return $deleted;
    }

    /**
     * Hapus semua notifikasi user
     */
    public function deleteAll(int $userId)
    {
        $count = Notification::forUser($userId)->delete();
        
        // Clear cache setelah delete all
        $this->clearNotificationCache($userId);
        
        return $count;
    }

    /**
     * Hitung jumlah notifikasi belum dibaca (dengan cache)
     */
    public function getUnreadCount(int $userId)
    {
        $cacheKey = "notifications:unread_count:{$userId}";
        
        return Cache::remember($cacheKey, 300, function () use ($userId) {
            return Notification::forUser($userId)->unread()->count('id_notification');
        });
    }
    
    /**
     * Clear cache notifikasi user
     */
    protected function clearNotificationCache(int $userId)
    {
        Cache::forget("notifications:unread_count:{$userId}");
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

    /**
     * Notifikasi saat pembaruan profil disetujui
     */
    public function notifyProfileUpdateApproved(int $userId, int $pendingId, string $sectionLabel)
    {
        return $this->create(
            $userId,
            'profile_update',
            'Pembaruan Profil Disetujui',
            "Pembaruan {$sectionLabel} Anda telah disetujui oleh admin dan sekarang terlihat di profil Anda.",
            ['pending_id' => $pendingId, 'section' => $sectionLabel]
        );
    }

    /**
     * Notifikasi saat pembaruan profil ditolak
     */
    public function notifyProfileUpdateRejected(int $userId, int $pendingId, string $sectionLabel, ?string $reason = null)
    {
        $message = "Pembaruan {$sectionLabel} Anda ditolak oleh admin.";
        if ($reason) {
            $message .= ' Alasan: ' . $reason;
        }

        return $this->create(
            $userId,
            'profile_update',
            'Pembaruan Profil Ditolak',
            $message,
            ['pending_id' => $pendingId, 'section' => $sectionLabel, 'reason' => $reason]
        );
    }

    /**
     * Notifikasi saat ada pengumuman baru
     */
    public function notifyNewPengumuman(int $userId, int $pengumumanId, string $judul)
    {
        return $this->create(
            $userId,
            'pengumuman',
            'Pengumuman Baru',
            "Ada pengumuman baru: \"{$judul}\". Silakan baca untuk informasi terbaru.",
            ['pengumuman_id' => $pengumumanId, 'pengumuman_judul' => $judul]
        );
    }

    // ========================================
    // NOTIFIKASI KONEKSI ALUMNI
    // ========================================

    /**
     * Notifikasi saat ada permintaan koneksi baru.
     */
    public function notifyConnectionRequest(int $userId, int $requesterAlumniId, string $requesterName)
    {
        return $this->create(
            $userId,
            'connection',
            'Permintaan Koneksi Baru',
            "{$requesterName} ingin terhubung dengan Anda. Lihat profil dan terima atau tolak permintaan koneksi.",
            ['alumni_id' => $requesterAlumniId, 'alumni_name' => $requesterName]
        );
    }

    /**
     * Notifikasi saat permintaan koneksi diterima.
     */
    public function notifyConnectionAccepted(int $userId, int $accepterAlumniId, string $accepterName)
    {
        return $this->create(
            $userId,
            'connection',
            'Koneksi Diterima',
            "{$accepterName} menerima permintaan koneksi Anda. Sekarang Anda saling terhubung!",
            ['alumni_id' => $accepterAlumniId, 'alumni_name' => $accepterName]
        );
    }
}
