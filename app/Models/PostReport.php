<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostReport extends Model
{
    use HasFactory;

    protected $table = 'post_reports';
    protected $primaryKey = 'id_report';

    protected $fillable = [
        'id_post',
        'id_alumni',
        'reason',
        'description',
        'status',
    ];

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

    // =====================
    // SCOPES
    // =====================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
