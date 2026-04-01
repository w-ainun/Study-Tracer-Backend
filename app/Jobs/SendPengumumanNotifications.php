<?php

namespace App\Jobs;

use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendPengumumanNotifications implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 120;

    /**
     * @param array<int> $userIds
     */
    public function __construct(
        private array $userIds,
        private int $pengumumanId,
        private string $pengumumanJudul,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(NotificationService $notificationService): void
    {
        foreach ($this->userIds as $userId) {
            $notificationService->notifyNewPengumuman(
                $userId,
                $this->pengumumanId,
                $this->pengumumanJudul,
            );
        }
    }
}
