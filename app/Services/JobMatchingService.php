<?php

namespace App\Services;

use App\Models\Alumni;
use App\Models\Lowongan;
use App\Models\Notification;
use App\Jobs\SendNotificationJob;
use Illuminate\Support\Facades\DB;

class JobMatchingService
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get matching lowongan for an alumni based on their skills.
     * Returns published lowongan that share at least one skill with the alumni.
     */
    public function getMatchingJobs(int $alumniId, int $limit = 5): array
    {
        $alumni = Alumni::with('skills')->find($alumniId);
        if (!$alumni) {
            return [];
        }

        $skillIds = $alumni->skills->pluck('id_skills')->toArray();
        if (empty($skillIds)) {
            // If no skills, return latest published lowongan
            return Lowongan::with(['perusahaan', 'pekerjaan', 'skills'])
                ->where('status', 'published')
                ->where('approval_status', 'approved')
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get()
                ->toArray();
        }

        // Find lowongan with matching skills, sorted by match count
        $lowongan = Lowongan::with(['perusahaan', 'pekerjaan', 'skills'])
            ->where('status', 'published')
            ->where('approval_status', 'approved')
            ->whereHas('skills', function ($q) use ($skillIds) {
                $q->whereIn('skills.id_skills', $skillIds);
            })
            ->withCount(['skills as match_count' => function ($q) use ($skillIds) {
                $q->whereIn('skills.id_skills', $skillIds);
            }])
            ->orderByDesc('match_count')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $lowongan->toArray();
    }

    /**
     * Send job recommendation notifications to an alumni who is "Belum Bekerja".
     * Avoids sending duplicate notifications for the same lowongan.
     */
    public function sendJobNotifications(int $userId, int $alumniId): int
    {
        $matchingJobs = $this->getMatchingJobs($alumniId, 3);
        if (empty($matchingJobs)) {
            return 0;
        }

        // Get already notified lowongan IDs to avoid duplicates
        $alreadyNotifiedIds = Notification::where('id_users', $userId)
            ->where('type', 'job_recommendation')
            ->where('created_at', '>=', now()->subDays(7)) // Only check last 7 days
            ->get()
            ->pluck('data')
            ->filter()
            ->pluck('lowongan_id')
            ->toArray();

        $sentCount = 0;
        foreach ($matchingJobs as $job) {
            $lowonganId = $job['id_lowongan'];

            // Skip if already notified within the last 7 days
            if (in_array($lowonganId, $alreadyNotifiedIds)) {
                continue;
            }

            $jobTitle = $job['judul_lowongan'] ?? 'Lowongan';
            $companyName = $job['perusahaan']['nama_perusahaan'] ?? '';
            $location = $companyName ? " di {$companyName}" : '';

            $this->notificationService->create(
                $userId,
                'job_recommendation',
                'Lowongan Sesuai Keahlian Anda',
                "Ada lowongan \"{$jobTitle}\"{$location} yang sesuai dengan keahlian Anda. Lihat dan lamar sekarang!",
                [
                    'lowongan_id' => $lowonganId,
                    'job_title' => $jobTitle,
                    'company_name' => $companyName,
                ]
            );

            $sentCount++;
        }

        return $sentCount;
    }

    /**
     * Check if an alumni has "Belum Bekerja" status.
     */
    public function isAlumniBelumBekerja(int $alumniId): bool
    {
        $latestRiwayat = DB::table('riwayat_status')
            ->join('status', 'riwayat_status.id_status', '=', 'status.id_status')
            ->where('riwayat_status.id_alumni', $alumniId)
            ->where('riwayat_status.approval_status', 'approved')
            ->orderByDesc('riwayat_status.id_riwayat')
            ->select('status.nama_status')
            ->first();

        if (!$latestRiwayat) {
            // No approved career status = treat as belum bekerja
            return true;
        }

        return $latestRiwayat->nama_status === 'Belum Bekerja';
    }
}
