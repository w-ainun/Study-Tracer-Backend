<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversationParticipant extends Model
{
    use HasFactory;

    protected $table = 'conversation_participants';
    protected $primaryKey = 'id_participant';

    protected $fillable = [
        'id_conversation',
        'id_users',
        'role',
        'is_pinned',
        'is_archived',
        'is_muted',
        'last_read_at',
        'joined_at',
        'left_at',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned'    => 'boolean',
            'is_archived'  => 'boolean',
            'is_muted'     => 'boolean',
            'last_read_at' => 'datetime',
            'joined_at'    => 'datetime',
            'left_at'      => 'datetime',
        ];
    }

    // =====================
    // RELATIONSHIPS
    // =====================

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'id_conversation', 'id_conversation');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_users', 'id_users');
    }

    // =====================
    // SCOPES
    // =====================

    public function scopeActive($query)
    {
        return $query->whereNull('left_at');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('id_users', $userId);
    }
}
