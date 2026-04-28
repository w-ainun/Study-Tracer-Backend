<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $post,
        private array $targetUserIds,
    ) {}

    /**
     * Broadcast to each connection's private channel.
     */
    public function broadcastOn(): array
    {
        $channels = [];
        foreach ($this->targetUserIds as $userId) {
            $channels[] = new PrivateChannel("feed.{$userId}");
        }
        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'post.created';
    }

    public function broadcastWith(): array
    {
        return [
            'post' => $this->post,
        ];
    }
}
