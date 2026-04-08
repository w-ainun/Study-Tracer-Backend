<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardStatsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $type, // 'new_registration' | 'new_lowongan' | 'kuesioner_response' | 'career_update' | 'profile_update'
        public ?array $summary = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'dashboard.stats-updated';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'summary' => $this->summary,
            'timestamp' => now()->toISOString(),
        ];
    }
}
