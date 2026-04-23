<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $table = 'conversations';
    protected $primaryKey = 'id_conversation';

    protected $fillable = [
        'type',
        'group_name',
        'group_avatar',
        'created_by',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    /**
     * Participants (pivot) of this conversation.
     */
    public function participants()
    {
        return $this->hasMany(ConversationParticipant::class, 'id_conversation', 'id_conversation');
    }

    /**
     * Active participants (not left).
     */
    public function activeParticipants()
    {
        return $this->participants()->whereNull('left_at');
    }

    /**
     * Users in this conversation (through pivot).
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'conversation_participants', 'id_conversation', 'id_users')
            ->withPivot('role', 'is_pinned', 'is_archived', 'is_muted', 'last_read_at', 'joined_at', 'left_at')
            ->withTimestamps();
    }

    /**
     * Messages in this conversation.
     */
    public function messages()
    {
        return $this->hasMany(Message::class, 'id_conversation', 'id_conversation');
    }

    /**
     * The latest message (for conversation list).
     */
    public function latestMessage()
    {
        return $this->hasOne(Message::class, 'id_conversation', 'id_conversation')
            ->where('is_deleted', false)
            ->latest('created_at');
    }

    /**
     * Creator of the conversation.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id_users');
    }

    // =====================
    // SCOPES
    // =====================

    /**
     * Conversations a specific user is part of (and hasn't left).
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->whereHas('participants', function ($q) use ($userId) {
            $q->where('id_users', $userId)->whereNull('left_at');
        });
    }

    /**
     * Scope: only private (1-on-1) conversations.
     */
    public function scopePrivate($query)
    {
        return $query->where('type', 'private');
    }

    /**
     * Scope: only group conversations.
     */
    public function scopeGroup($query)
    {
        return $query->where('type', 'group');
    }

    // =====================
    // HELPERS
    // =====================

    /**
     * Get the other participant in a private conversation.
     */
    public function getOtherParticipant(int $userId)
    {
        return $this->activeParticipants()
            ->where('id_users', '!=', $userId)
            ->with('user.alumni')
            ->first();
    }

    /**
     * Count unread messages for a given user.
     */
    public function unreadCountFor(int $userId): int
    {
        $participant = $this->participants()
            ->where('id_users', $userId)
            ->whereNull('left_at')
            ->first();

        if (!$participant) {
            return 0;
        }

        $query = $this->messages()
            ->where('id_sender', '!=', $userId)
            ->where('is_deleted', false);

        if ($participant->last_read_at) {
            $query->where('created_at', '>', $participant->last_read_at);
        }

        return $query->count();
    }
}
