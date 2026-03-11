<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingProfileUpdate extends Model
{
    protected $table = 'pending_profile_updates';

    protected $fillable = [
        'id_alumni',
        'section',
        'action',
        'related_id',
        'old_data',
        'new_data',
        'foto_path',
        'gambar_path',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'old_data' => 'array',
            'new_data' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function alumni(): BelongsTo
    {
        return $this->belongsTo(Alumni::class, 'id_alumni', 'id_alumni');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'id_users');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForAlumni($query, int $alumniId)
    {
        return $query->where('id_alumni', $alumniId);
    }
}
