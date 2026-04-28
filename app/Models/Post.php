<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $table = 'posts';
    protected $primaryKey = 'id_post';

    protected $fillable = [
        'id_alumni',
        'content',
        'visibility',
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

    /**
     * Alumni pemilik postingan.
     */
    public function alumni()
    {
        return $this->belongsTo(Alumni::class, 'id_alumni', 'id_alumni');
    }

    /**
     * Gambar-gambar postingan.
     */
    public function images()
    {
        return $this->hasMany(PostImage::class, 'id_post', 'id_post')
                    ->orderBy('sort_order');
    }

    /**
     * Like pada postingan.
     */
    public function likes()
    {
        return $this->hasMany(PostLike::class, 'id_post', 'id_post');
    }

    /**
     * Komentar pada postingan (top-level only).
     */
    public function comments()
    {
        return $this->hasMany(PostComment::class, 'id_post', 'id_post')
                    ->whereNull('id_parent_comment')
                    ->where('is_active', true);
    }

    /**
     * Semua komentar (termasuk replies).
     */
    public function allComments()
    {
        return $this->hasMany(PostComment::class, 'id_post', 'id_post')
                    ->where('is_active', true);
    }

    /**
     * Laporan pada postingan.
     */
    public function reports()
    {
        return $this->hasMany(PostReport::class, 'id_post', 'id_post');
    }

    // =====================
    // SCOPES
    // =====================

    /**
     * Scope: hanya postingan aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: postingan publik.
     */
    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    /**
     * Scope: postingan oleh alumni tertentu.
     */
    public function scopeByAlumni($query, int $alumniId)
    {
        return $query->where('id_alumni', $alumniId);
    }

    // =====================
    // HELPER METHODS
    // =====================

    /**
     * Cek apakah alumni tertentu sudah like post ini.
     */
    public function isLikedBy(int $alumniId): bool
    {
        return $this->likes()->where('id_alumni', $alumniId)->exists();
    }
}
