<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostInteraction implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private int $postOwnerUserId,
        public string $type, // 'liked', 'commented'
        public array $data,
    ) {}

    /**
     * Broadcast to the post owner's private channel.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("feed.{$this->postOwnerUserId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'post.interaction';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'data' => $this->data,
        ];
    }
}
