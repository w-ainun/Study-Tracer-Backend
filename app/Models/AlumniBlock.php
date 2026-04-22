<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlumniBlock extends Model
{
    use HasFactory;

    protected $table = 'alumni_blocks';
    protected $primaryKey = 'id_block';

    protected $fillable = [
        'id_alumni_blocker',
        'id_alumni_blocked',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    /**
     * Alumni yang melakukan block.
     */
    public function blocker()
    {
        return $this->belongsTo(Alumni::class, 'id_alumni_blocker', 'id_alumni');
    }

    /**
     * Alumni yang di-block.
     */
    public function blocked()
    {
        return $this->belongsTo(Alumni::class, 'id_alumni_blocked', 'id_alumni');
    }

    // =====================
    // SCOPES
    // =====================

    /**
     * Scope: block oleh alumni tertentu.
     */
    public function scopeByBlocker($query, int $alumniId)
    {
        return $query->where('id_alumni_blocker', $alumniId);
    }

    /**
     * Scope: cek apakah ada block antara dua alumni (dari arah manapun).
     */
    public function scopeBetween($query, int $alumniIdA, int $alumniIdB)
    {
        return $query->where(function ($q) use ($alumniIdA, $alumniIdB) {
            $q->where(function ($inner) use ($alumniIdA, $alumniIdB) {
                $inner->where('id_alumni_blocker', $alumniIdA)
                      ->where('id_alumni_blocked', $alumniIdB);
            })->orWhere(function ($inner) use ($alumniIdA, $alumniIdB) {
                $inner->where('id_alumni_blocker', $alumniIdB)
                      ->where('id_alumni_blocked', $alumniIdA);
            });
        });
    }
}
