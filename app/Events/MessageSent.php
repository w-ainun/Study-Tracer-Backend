<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        private int $conversationId,
        public array $message,
        private array $participantUserIds,
    ) {}

    /**
     * Broadcast to each participant's private channel.
     */
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
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'message' => $this->message,
        ];
    }
}
