<?php

namespace App\Jobs;

use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendBulkNotifications implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 120;

    /**
     * @param array<int> $userIds
     */
    public function __construct(
        private array $userIds,
        private int $kuesionerId,
        private string $kuesionerTitle,
        private string $statusName,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(NotificationService $notificationService): void
    {
        foreach ($this->userIds as $userId) {
            $notificationService->notifyNewKuesioner(
                $userId,
                $this->kuesionerId,
                $this->kuesionerTitle,
                $this->statusName,
            );
        }
    }
}
