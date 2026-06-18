<?php

namespace App\Jobs;

use App\Services\JobMatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendJobRecommendationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $userId;
    public int $alumniId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId, int $alumniId)
    {
        $this->userId = $userId;
        $this->alumniId = $alumniId;
    }

    /**
     * Execute the job.
     * Checks if alumni is "Belum Bekerja" and sends matching job notifications.
     */
    public function handle(JobMatchingService $jobMatchingService): void
    {
        // Double-check status before sending
        if ($jobMatchingService->isAlumniBelumBekerja($this->alumniId)) {
            $jobMatchingService->sendJobNotifications($this->userId, $this->alumniId);
        }
    }
}
