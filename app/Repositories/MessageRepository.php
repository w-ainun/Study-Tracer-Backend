<?php

namespace App\Repositories;

use App\Interfaces\MessageRepositoryInterface;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use Illuminate\Support\Facades\DB;

class MessageRepository implements MessageRepositoryInterface
{
    // =====================
    // CONVERSATIONS
    // =====================

    /**
     * Get paginated conversations for a user, ordered by latest message.
     * Supports search by contact name or group name.
     */
    public function getConversationsForUser(int $userId, ?string $search = null, int $perPage = 20)
    {
        $query = Conversation::forUser($userId)
            ->select('conversations.*')
            ->with([
                'latestMessage.sender.alumni',
                'activeParticipants.user.alumni',
            ])
            ->withCount(['messages as unread_count' => function ($q) use ($userId) {
                $q->where('is_deleted', false)
                  ->where('id_sender', '!=', $userId)
                  ->whereRaw(
                      'created_at > COALESCE(
                          (SELECT last_read_at FROM conversation_participants
                           WHERE conversation_participants.id_conversation = messages.id_conversation
                           AND conversation_participants.id_users = ?
                           AND conversation_participants.left_at IS NULL
                           LIMIT 1),
                          \'1970-01-01\'
                      )',
                      [$userId]
                  );
            }]);

        // Search by group name or participant alumni name
        if ($search) {
            $query->where(function ($q) use ($search, $userId) {
                // Group name match
                $q->where('group_name', 'like', "%{$search}%")
                  // Or any participant's alumni name matches (for private chats)
                  ->orWhereHas('activeParticipants', function ($pq) use ($search, $userId) {
                      $pq->where('id_users', '!=', $userId)
                         ->whereHas('user.alumni', function ($aq) use ($search) {
                             $aq->where('nama_alumni', 'like', "%{$search}%");
                         });
                  });
            });
        }

        // Exclude archived conversations
        $query->whereHas('participants', function ($q) use ($userId) {
            $q->where('id_users', $userId)
              ->whereNull('left_at')
              ->where('is_archived', false);
        });

        // Order by latest message timestamp (most recent first), pinned first
        return $query
            ->leftJoin('messages', function ($join) {
                $join->on('conversations.id_conversation', '=', 'messages.id_conversation')
                     ->whereRaw('messages.id_message = (SELECT MAX(m2.id_message) FROM messages m2 WHERE m2.id_conversation = conversations.id_conversation AND m2.is_deleted = 0)');
            })
            ->orderByRaw('
                (SELECT cp.is_pinned FROM conversation_participants cp
                 WHERE cp.id_conversation = conversations.id_conversation
                 AND cp.id_users = ? AND cp.left_at IS NULL LIMIT 1) DESC
            ', [$userId])
            ->orderByDesc('messages.created_at')
            ->orderByDesc('conversations.created_at')
            ->paginate($perPage);
    }

    public function findConversation(int $conversationId)
    {
        return Conversation::with([
            'activeParticipants.user.alumni',
            'latestMessage.sender.alumni',
        ])->findOrFail($conversationId);
    }

    /**
     * Find existing private (1-on-1) conversation between two users.
     */
    public function findPrivateConversation(int $userIdA, int $userIdB)
    {
        return Conversation::where('type', 'private')
            ->whereHas('participants', function ($q) use ($userIdA) {
                $q->where('id_users', $userIdA)->whereNull('left_at');
            })
            ->whereHas('participants', function ($q) use ($userIdB) {
                $q->where('id_users', $userIdB)->whereNull('left_at');
            })
            ->first();
    }

    /**
     * Create a new conversation with participants.
     */
    public function createConversation(array $data, array $participantIds)
    {
        return DB::transaction(function () use ($data, $participantIds) {
            $conversation = Conversation::create($data);

            foreach ($participantIds as $userId) {
                ConversationParticipant::create([
                    'id_conversation' => $conversation->id_conversation,
                    'id_users'        => $userId,
                    'role'            => $userId === ($data['created_by'] ?? null) ? 'admin' : 'member',
                    'joined_at'       => now(),
                ]);
            }

            return $conversation->fresh([
                'activeParticipants.user.alumni',
            ]);
        });
    }

    public function deleteConversation(int $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);
        $conversation->delete();
    }

    // =====================
    // PARTICIPANTS
    // =====================

    public function addParticipant(int $conversationId, int $userId, string $role = 'member')
    {
        return ConversationParticipant::updateOrCreate(
            [
                'id_conversation' => $conversationId,
                'id_users'        => $userId,
            ],
            [
                'role'      => $role,
                'joined_at' => now(),
                'left_at'   => null,
            ]
        );
    }

    public function removeParticipant(int $conversationId, int $userId)
    {
        ConversationParticipant::where('id_conversation', $conversationId)
            ->where('id_users', $userId)
            ->update(['left_at' => now()]);
    }

    public function getParticipantIds(int $conversationId): array
    {
        return ConversationParticipant::where('id_conversation', $conversationId)
            ->whereNull('left_at')
            ->pluck('id_users')
            ->toArray();
    }

    public function isParticipant(int $conversationId, int $userId): bool
    {
        return ConversationParticipant::where('id_conversation', $conversationId)
            ->where('id_users', $userId)
            ->whereNull('left_at')
            ->exists();
    }

    public function updateParticipantSettings(int $conversationId, int $userId, array $data)
    {
        return ConversationParticipant::where('id_conversation', $conversationId)
            ->where('id_users', $userId)
            ->whereNull('left_at')
            ->update($data);
    }

    public function unarchiveConversationForParticipants(int $conversationId)
    {
        return ConversationParticipant::where('id_conversation', $conversationId)
            ->whereNull('left_at')
            ->update(['is_archived' => false]);
    }

    public function markAsRead(int $conversationId, int $userId)
    {
        return ConversationParticipant::where('id_conversation', $conversationId)
            ->where('id_users', $userId)
            ->whereNull('left_at')
            ->update(['last_read_at' => now()]);
    }

    // =====================
    // MESSAGES
    // =====================

    /**
     * Get paginated messages for a conversation, newest first.
     */
    public function getMessages(int $conversationId, int $perPage = 30)
    {
        return Message::inConversation($conversationId)
            ->visible()
            ->with([
                'sender.alumni',
                'replyTo' => function ($q) {
                    $q->with('sender.alumni');
                },
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findMessage(int $messageId)
    {
        return Message::with(['sender.alumni', 'replyTo.sender.alumni'])->findOrFail($messageId);
    }

    public function createMessage(array $data)
    {
        $message = Message::create($data);
        return $message->fresh(['sender.alumni', 'replyTo.sender.alumni']);
    }

    public function deleteMessage(int $messageId)
    {
        return Message::where('id_message', $messageId)
            ->update(['is_deleted' => true]);
    }

    public function clearMessages(int $conversationId)
    {
        return Message::where('id_conversation', $conversationId)
            ->update(['is_deleted' => true]);
    }

    // =====================
    // STATS
    // =====================

    /**
     * Get total unread message count across all conversations.
     */
    public function getTotalUnreadCount(int $userId): int
    {
        return DB::selectOne("
            SELECT COALESCE(SUM(unread), 0) as total
            FROM (
                SELECT COUNT(*) as unread
                FROM messages m
                INNER JOIN conversation_participants cp
                    ON cp.id_conversation = m.id_conversation
                    AND cp.id_users = ?
                    AND cp.left_at IS NULL
                WHERE m.id_sender != ?
                  AND m.is_deleted = 0
                  AND m.created_at > COALESCE(cp.last_read_at, '1970-01-01')
                GROUP BY m.id_conversation
            ) sub
        ", [$userId, $userId])->total ?? 0;
    }
}
