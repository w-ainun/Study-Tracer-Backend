<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PengumumanCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $pengumumanId,
        public string $judul,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('alumni'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'pengumuman.created';
    }

    public function broadcastWith(): array
    {
        return [
            'pengumuman_id' => $this->pengumumanId,
            'judul' => $this->judul,
        ];
    }
}
