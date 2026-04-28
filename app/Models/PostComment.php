<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostComment extends Model
{
    use HasFactory;

    protected $table = 'post_comments';
    protected $primaryKey = 'id_comment';

    protected $fillable = [
        'id_post',
        'id_alumni',
        'id_parent_comment',
        'content',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // =====================
    // RELATIONSHIPS
    // =====================

    public function post()
    {
        return $this->belongsTo(Post::class, 'id_post', 'id_post');
    }

    public function alumni()
    {
        return $this->belongsTo(Alumni::class, 'id_alumni', 'id_alumni');
    }

    /**
     * Parent comment (untuk reply).
     */
    public function parent()
    {
        return $this->belongsTo(PostComment::class, 'id_parent_comment', 'id_comment');
    }

    /**
     * Replies pada komentar ini.
     */
    public function replies()
    {
        return $this->hasMany(PostComment::class, 'id_parent_comment', 'id_comment')
                    ->where('is_active', true)
                    ->orderBy('created_at');
    }

    // =====================
    // SCOPES
    // =====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('id_parent_comment');
    }
}
