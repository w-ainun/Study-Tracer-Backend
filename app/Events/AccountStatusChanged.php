<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccountStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private int $userId,
        public string $status, // 'verified' | 'rejected' | 'banned'
        public ?string $reason = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->userId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'account.status-changed';
    }

    public function broadcastWith(): array
    {
        return [
            'status' => $this->status,
            'reason' => $this->reason,
        ];
    }
}
