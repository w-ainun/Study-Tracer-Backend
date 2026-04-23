<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TypingIndicator implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private int $conversationId,
        private int $userId,
        private string $userName,
        private bool $isTyping,
        private array $participantUserIds,
    ) {}

    public function broadcastOn(): array
    {
        $channels = [];
        foreach ($this->participantUserIds as $userId) {
            if ($userId !== $this->userId) {
                $channels[] = new PrivateChannel("chat.{$userId}");
            }
        }
        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'typing.indicator';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'is_typing' => $this->isTyping,
        ];
    }
}
