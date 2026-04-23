<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Private channel authorization for Reverb WebSocket connections.
| These callbacks verify the authenticated Sanctum user has access
| to the requested channel.
|
*/

// Per-user private channel — notifikasi, kuesioner, account status, etc.
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id_users === (int) $userId;
});

// Admin-only channel — dashboard stats, pending approvals, etc.
Broadcast::channel('admin', function ($user) {
    return $user->role === 'admin';
});

// Alumni-only channel — pengumuman broadcast, etc.
Broadcast::channel('alumni', function ($user) {
    return $user->role === 'alumni';
});

// Per-user chat channel — real-time messaging (Pesan)
Broadcast::channel('chat.{userId}', function ($user, $userId) {
    return (int) $user->id_users === (int) $userId;
});
