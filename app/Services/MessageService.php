<?php

namespace App\Services;

use App\Events\MessageDeleted;
use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Events\TypingIndicator;
use App\Models\Alumni;
use App\Models\AlumniBlock;
use App\Repositories\MessageRepository;
use Illuminate\Support\Facades\Storage;

class MessageService
{
    public function __construct(
        private MessageRepository $messageRepository,
    ) {}

    // =====================
    // CONVERSATIONS
    // =====================

    /**
     * Get paginated conversations for a user.
     */
    public function getConversations(int $userId, ?string $search = null, int $perPage = 20)
    {
        return $this->messageRepository->getConversationsForUser($userId, $search, $perPage);
    }

    /**
     * Get a single conversation detail (with authorization check).
     */
    public function getConversation(int $userId, int $conversationId)
    {
        $this->ensureParticipant($conversationId, $userId);
        return $this->messageRepository->findConversation($conversationId);
    }

    /**
     * Get or create a private (1-on-1) conversation between the current user and a target alumni.
     * Used when clicking "Kirim Pesan" from the alumni profile.
     */
    public function getOrCreatePrivateConversation(int $userId, int $targetAlumniId)
    {
        // Resolve target user ID from alumni ID
        $targetAlumni = Alumni::where('id_alumni', $targetAlumniId)->firstOrFail();
        $targetUserId = $targetAlumni->id_users;

        if ($userId === $targetUserId) {
            throw new \Exception('Tidak bisa mengirim pesan ke diri sendiri.');
        }

        // Check block status
        $this->ensureNotBlocked($userId, $targetUserId);

        // Find existing private conversation
        $conversation = $this->messageRepository->findPrivateConversation($userId, $targetUserId);

        if ($conversation) {
            $this->messageRepository->updateParticipantSettings($conversation->id_conversation, $userId, [
                'is_archived' => false,
            ]);
            return $conversation->load(['activeParticipants.user.alumni', 'latestMessage.sender.alumni']);
        }

        // Create new private conversation
        return $this->messageRepository->createConversation(
            [
                'type'       => 'private',
                'created_by' => $userId,
            ],
            [$userId, $targetUserId]
        );
    }

    /**
     * Create a group conversation.
     */
    public function createGroupConversation(int $userId, string $groupName, array $participantAlumniIds, ?string $avatarPath = null)
    {
        // Resolve alumni IDs to user IDs
        $userIds = [$userId]; // Creator is always included
        foreach ($participantAlumniIds as $alumniId) {
            $alumni = Alumni::where('id_alumni', $alumniId)->firstOrFail();
            if ($alumni->id_users !== $userId) {
                $this->ensureNotBlocked($userId, $alumni->id_users);
                $userIds[] = $alumni->id_users;
            }
        }

        if (count($userIds) < 2) {
            throw new \Exception('Grup minimal harus memiliki 2 anggota.');
        }

        return $this->messageRepository->createConversation(
            [
                'type'         => 'group',
                'group_name'   => $groupName,
                'group_avatar' => $avatarPath,
                'created_by'   => $userId,
            ],
            array_unique($userIds)
        );
    }

    /**
     * Update group conversation info.
     */
    public function updateGroupConversation(int $userId, int $conversationId, array $data)
    {
        $conversation = $this->messageRepository->findConversation($conversationId);

        if ($conversation->type !== 'group') {
            throw new \Exception('Hanya grup yang bisa diperbarui.');
        }

        $this->ensureParticipant($conversationId, $userId);

        $conversation->update(array_filter([
            'group_name'   => $data['group_name'] ?? null,
            'group_avatar' => $data['group_avatar'] ?? null,
        ]));

        return $conversation->fresh(['activeParticipants.user.alumni']);
    }

    /**
     * Leave a group conversation.
     */
    public function leaveConversation(int $userId, int $conversationId)
    {
        $conversation = $this->messageRepository->findConversation($conversationId);

        if ($conversation->type !== 'group') {
            throw new \Exception('Tidak bisa keluar dari percakapan pribadi. Gunakan hapus percakapan.');
        }

        $this->ensureParticipant($conversationId, $userId);
        $this->messageRepository->removeParticipant($conversationId, $userId);

        // Send system message
        $user = \App\Models\User::with('alumni')->find($userId);
        $senderName = $user->alumni->nama_alumni ?? 'User';

        $systemMsg = $this->messageRepository->createMessage([
            'id_conversation' => $conversationId,
            'id_sender'       => $userId,
            'type'            => 'system',
            'body'            => "{$senderName} meninggalkan grup.",
        ]);

        // Broadcast to remaining participants
        $participantIds = $this->messageRepository->getParticipantIds($conversationId);
        broadcast(new MessageSent($conversationId, $this->formatMessage($systemMsg), $participantIds));

        // If no participants left, delete the conversation
        if (count($participantIds) === 0) {
            $this->messageRepository->deleteConversation($conversationId);
        }
    }

    /**
     * Delete a conversation (only for the current user — hides it).
     * For group chats, only the creator can delete.
     */
    public function deleteConversationForUser(int $userId, int $conversationId)
    {
        $this->ensureParticipant($conversationId, $userId);
        $conversation = $this->messageRepository->findConversation($conversationId);

        if ($conversation->type === 'group' && (int) $conversation->created_by !== $userId) {
            throw new \Exception('Hanya pembuat grup yang dapat menghapus percakapan grup.');
        }

        $this->messageRepository->updateParticipantSettings($conversationId, $userId, [
            'is_archived' => true,
        ]);
    }

    /**
     * Clear all messages in a conversation.
     */
    public function clearMessagesForUser(int $userId, int $conversationId)
    {
        $this->ensureParticipant($conversationId, $userId);
        $this->messageRepository->clearMessages($conversationId);
    }

    // =====================
    // PARTICIPANT SETTINGS
    // =====================

    /**
     * Toggle pin/unpin a conversation.
     */
    public function togglePin(int $userId, int $conversationId)
    {
        $this->ensureParticipant($conversationId, $userId);

        $participant = \App\Models\ConversationParticipant::where('id_conversation', $conversationId)
            ->where('id_users', $userId)
            ->whereNull('left_at')
            ->firstOrFail();

        $participant->update(['is_pinned' => !$participant->is_pinned]);

        return $participant->is_pinned;
    }

    /**
     * Toggle mute/unmute a conversation.
     */
    public function toggleMute(int $userId, int $conversationId)
    {
        $this->ensureParticipant($conversationId, $userId);

        $participant = \App\Models\ConversationParticipant::where('id_conversation', $conversationId)
            ->where('id_users', $userId)
            ->whereNull('left_at')
            ->firstOrFail();

        $participant->update(['is_muted' => !$participant->is_muted]);

        return $participant->is_muted;
    }

    // =====================
    // MESSAGES
    // =====================

    /**
     * Get messages for a conversation.
     */
    public function getMessages(int $userId, int $conversationId, int $perPage = 30)
    {
        $this->ensureParticipant($conversationId, $userId);
        return $this->messageRepository->getMessages($conversationId, $perPage);
    }

    /**
     * Send a text message.
     */
    public function sendMessage(int $userId, int $conversationId, array $data)
    {
        $this->ensureParticipant($conversationId, $userId);

        $messageData = [
            'id_conversation' => $conversationId,
            'id_sender'       => $userId,
            'type'            => $data['type'] ?? 'text',
            'body'            => $data['body'] ?? null,
            'reply_to_id'     => $data['reply_to_id'] ?? null,
        ];

        // Handle file uploads (image, file, gif)
        if (isset($data['file']) && $data['file']) {
            $file = $data['file'];
            $messageData['type'] = $data['type'] ?? $this->detectFileType($file->getMimeType());
            $path = $file->store('messages/' . $conversationId, 'public');
            $messageData['file_url']  = Storage::url($path);
            $messageData['file_name'] = $file->getClientOriginalName();
            $messageData['file_mime'] = $file->getMimeType();
            $messageData['file_size'] = $file->getSize();
        }

        // Handle GIF URL (from GIPHY/Tenor)
        if (isset($data['gif_url']) && $data['gif_url']) {
            $messageData['type']     = 'gif';
            $messageData['file_url'] = $data['gif_url'];
        }

        $message = $this->messageRepository->createMessage($messageData);

        // Unarchive conversation for all active participants (so the chat reappears in their chat list)
        $this->messageRepository->unarchiveConversationForParticipants($conversationId);

        // Broadcast to participants
        $participantIds = $this->messageRepository->getParticipantIds($conversationId);
        $formattedMessage = $this->formatMessage($message);

        broadcast(new MessageSent($conversationId, $formattedMessage, $participantIds));

        return $message;
    }

    /**
     * Delete (soft) a message.
     */
    public function deleteMessage(int $userId, int $messageId)
    {
        $message = $this->messageRepository->findMessage($messageId);

        if ((int) $message->id_sender !== $userId) {
            throw new \Exception('Hanya pengirim yang bisa menghapus pesan.');
        }

        $this->messageRepository->deleteMessage($messageId);

        // Broadcast deletion
        $participantIds = $this->messageRepository->getParticipantIds($message->id_conversation);
        broadcast(new MessageDeleted($message->id_conversation, $messageId, $participantIds));
    }

    // =====================
    // READ RECEIPTS
    // =====================

    /**
     * Mark all messages in a conversation as read.
     */
    public function markAsRead(int $userId, int $conversationId)
    {
        $this->ensureParticipant($conversationId, $userId);
        $this->messageRepository->markAsRead($conversationId, $userId);

        // Broadcast read receipt
        $participantIds = $this->messageRepository->getParticipantIds($conversationId);
        broadcast(new MessageRead($conversationId, $userId, now()->toISOString(), $participantIds));
    }

    // =====================
    // TYPING INDICATOR
    // =====================

    /**
     * Broadcast typing indicator.
     */
    public function broadcastTyping(int $userId, int $conversationId, bool $isTyping)
    {
        $this->ensureParticipant($conversationId, $userId);

        $user = \App\Models\User::with('alumni')->find($userId);
        $userName = $user->alumni->nama_alumni ?? 'User';

        $participantIds = $this->messageRepository->getParticipantIds($conversationId);
        broadcast(new TypingIndicator($conversationId, $userId, $userName, $isTyping, $participantIds));
    }

    // =====================
    // STATS
    // =====================

    /**
     * Total unread messages across all conversations.
     */
    public function getTotalUnreadCount(int $userId): int
    {
        return $this->messageRepository->getTotalUnreadCount($userId);
    }

    // =====================
    // CONTACT LIST (Connections)
    // =====================

    /**
     * Get list of alumni the user can message (accepted connections).
     * Used for the "New Chat" modal search.
     */
    public function getMessageableContacts(int $userId, ?string $search = null, int $limit = 20)
    {
        $alumni = Alumni::whereHas('user', function ($q) use ($userId) {
            $q->where('id_users', $userId);
        })->first();

        if (!$alumni) {
            return collect();
        }

        $alumniId = $alumni->id_alumni;

        // Get connected alumni IDs (accepted connections)
        $connectedIds = \App\Models\AlumniConnection::where(function ($q) use ($alumniId) {
                $q->where('id_alumni_requester', $alumniId)
                  ->orWhere('id_alumni_addressee', $alumniId);
            })
            ->where('status', 'accepted')
            ->get()
            ->map(function ($conn) use ($alumniId) {
                return $conn->id_alumni_requester === $alumniId
                    ? $conn->id_alumni_addressee
                    : $conn->id_alumni_requester;
            })
            ->unique()
            ->values();

        // Filter by search
        $query = Alumni::whereIn('id_alumni', $connectedIds)
            ->with(['user', 'jurusan']);

        if ($search) {
            $query->where('nama_alumni', 'like', "%{$search}%");
        }

        // Exclude blocked alumni
        $blockedIds = AlumniBlock::where('id_alumni_blocker', $alumniId)
            ->pluck('id_alumni_blocked');
        $blockedByIds = AlumniBlock::where('id_alumni_blocked', $alumniId)
            ->pluck('id_alumni_blocker');

        $query->whereNotIn('id_alumni', $blockedIds->merge($blockedByIds));

        return $query->limit($limit)->get();
    }

    // =====================
    // HELPERS
    // =====================

    private function ensureParticipant(int $conversationId, int $userId): void
    {
        if (!$this->messageRepository->isParticipant($conversationId, $userId)) {
            throw new \Exception('Anda bukan anggota percakapan ini.');
        }
    }

    private function ensureNotBlocked(int $userIdA, int $userIdB): void
    {
        $alumniA = Alumni::where('id_users', $userIdA)->first();
        $alumniB = Alumni::where('id_users', $userIdB)->first();

        if (!$alumniA || !$alumniB) return;

        $isBlocked = AlumniBlock::where(function ($q) use ($alumniA, $alumniB) {
            $q->where('id_alumni_blocker', $alumniA->id_alumni)
              ->where('id_alumni_blocked', $alumniB->id_alumni);
        })->orWhere(function ($q) use ($alumniA, $alumniB) {
            $q->where('id_alumni_blocker', $alumniB->id_alumni)
              ->where('id_alumni_blocked', $alumniA->id_alumni);
        })->exists();

        if ($isBlocked) {
            throw new \Exception('Tidak dapat mengirim pesan ke pengguna ini.');
        }
    }

    /**
     * Detect message type from MIME type.
     */
    private function detectFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return str_contains($mimeType, 'gif') ? 'gif' : 'image';
        }
        return 'file';
    }

    /**
     * Format a message model into an array for broadcasting.
     */
    private function formatMessage($message): array
    {
        $sender = $message->sender;
        $alumni = $sender?->alumni;

        $formatted = [
            'id_message'      => $message->id_message,
            'id_conversation' => $message->id_conversation,
            'type'            => $message->type,
            'body'            => $message->body,
            'file_url'        => $message->file_url,
            'file_name'       => $message->file_name,
            'file_mime'       => $message->file_mime,
            'file_size'       => $message->file_size,
            'is_deleted'      => $message->is_deleted,
            'created_at'      => $message->created_at->toISOString(),
            'sender' => [
                'id_users'     => $sender?->id_users,
                'id_alumni'    => $alumni?->id_alumni,
                'nama_alumni'  => $alumni?->nama_alumni ?? 'User',
                'foto'         => $alumni?->foto,
            ],
        ];

        // Include reply-to info if present
        if ($message->reply_to_id && $message->replyTo) {
            $replySender = $message->replyTo->sender;
            $replyAlumni = $replySender?->alumni;
            $formatted['reply_to'] = [
                'id_message' => $message->replyTo->id_message,
                'body'       => $message->replyTo->body,
                'type'       => $message->replyTo->type,
                'sender'     => [
                    'nama_alumni' => $replyAlumni?->nama_alumni ?? 'User',
                ],
            ];
        }

        return $formatted;
    }
}
