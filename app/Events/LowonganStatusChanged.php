<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowonganStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private int $userId,
        public int $lowonganId,
        public string $jobTitle,
        public string $status, // 'approved' | 'rejected'
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->userId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'lowongan.status-changed';
    }

    public function broadcastWith(): array
    {
        return [
            'lowongan_id' => $this->lowonganId,
            'job_title' => $this->jobTitle,
            'status' => $this->status,
        ];
    }
}
