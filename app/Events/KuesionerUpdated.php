<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KuesionerUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private int $userId,
        public int $kuesionerId,
        public string $title,
        public string $action, // 'activated' | 'deactivated'
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->userId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'kuesioner.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'kuesioner_id' => $this->kuesionerId,
            'title' => $this->title,
            'action' => $this->action,
        ];
    }
}
