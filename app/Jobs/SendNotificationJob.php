<?php

namespace App\Jobs;

use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Generic job to offload single notification creation to queue.
 * Usage: SendNotificationJob::dispatch($userId, 'notifyAccountVerified');
 *        SendNotificationJob::dispatch($userId, 'notifyLowonganApproved', [$lowonganId, $jobTitle]);
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private int $userId,
        private string $method,
        private array $args = []
    ) {}

    public function handle(NotificationService $service): void
    {
        $service->{$this->method}($this->userId, ...$this->args);
    }
}
