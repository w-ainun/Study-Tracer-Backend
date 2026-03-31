<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengumuman extends Model
{
    use HasFactory;

    protected $table = 'pengumuman';
    protected $primaryKey = 'id_pengumuman';

    protected $fillable = [
        'judul',
        'konten',
        'foto',
        'status',
        'is_pinned',
        'id_users',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    // ── Relations ────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class, 'id_users', 'id_users');
    }
}
