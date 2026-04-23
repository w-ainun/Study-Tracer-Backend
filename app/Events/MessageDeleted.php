<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private int $conversationId,
        private int $messageId,
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
        return 'message.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'message_id' => $this->messageId,
        ];
    }
}
