<?php

namespace App\Interfaces;

interface MessageRepositoryInterface
{
    // =====================
    // CONVERSATIONS
    // =====================
    public function getConversationsForUser(int $userId, ?string $search = null, int $perPage = 20);
    public function findConversation(int $conversationId);
    public function findPrivateConversation(int $userIdA, int $userIdB);
    public function createConversation(array $data, array $participantIds);
    public function deleteConversation(int $conversationId);

    // =====================
    // PARTICIPANTS
    // =====================
    public function addParticipant(int $conversationId, int $userId, string $role = 'member');
    public function removeParticipant(int $conversationId, int $userId);
    public function getParticipantIds(int $conversationId): array;
    public function isParticipant(int $conversationId, int $userId): bool;
    public function updateParticipantSettings(int $conversationId, int $userId, array $data);
    public function unarchiveConversationForParticipants(int $conversationId);
    public function markAsRead(int $conversationId, int $userId);

    // =====================
    // MESSAGES
    // =====================
    public function getMessages(int $conversationId, int $perPage = 30);
    public function findMessage(int $messageId);
    public function createMessage(array $data);
    public function deleteMessage(int $messageId);
    public function clearMessages(int $conversationId);

    // =====================
    // STATS
    // =====================
    public function getTotalUnreadCount(int $userId): int;
}
