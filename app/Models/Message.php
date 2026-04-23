<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $table = 'messages';
    protected $primaryKey = 'id_message';

    protected $fillable = [
        'id_conversation',
        'id_sender',
        'type',
        'body',
        'file_url',
        'file_name',
        'file_mime',
        'file_size',
        'reply_to_id',
        'is_deleted',
    ];

    protected function casts(): array
    {
        return [
            'is_deleted' => 'boolean',
            'file_size'  => 'integer',
        ];
    }

    // =====================
    // RELATIONSHIPS
    // =====================

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'id_conversation', 'id_conversation');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'id_sender', 'id_users');
    }

    /**
     * The message this is replying to.
     */
    public function replyTo()
    {
        return $this->belongsTo(Message::class, 'reply_to_id', 'id_message');
    }

    /**
     * Replies to this message.
     */
    public function replies()
    {
        return $this->hasMany(Message::class, 'reply_to_id', 'id_message');
    }

    // =====================
    // SCOPES
    // =====================

    public function scopeVisible($query)
    {
        return $query->where('is_deleted', false);
    }

    public function scopeInConversation($query, int $conversationId)
    {
        return $query->where('id_conversation', $conversationId);
    }
}
