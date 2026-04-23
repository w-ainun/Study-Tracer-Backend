<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private int $conversationId,
        private int $readByUserId,
        private string $readAt,
        private array $participantUserIds,
    ) {}

    public function broadcastOn(): array
    {
        $channels = [];
        foreach ($this->participantUserIds as $userId) {
            $channels[] = new PrivateChannel("chat.{$userId}");
        }
        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'message.read';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'read_by_user_id' => $this->readByUserId,
            'read_at' => $this->readAt,
        ];
    }
}
