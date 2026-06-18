<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lamaran extends Model
{
    use HasFactory;

    protected $table = 'lamaran';
    protected $primaryKey = 'id_lamaran';

    protected $fillable = [
        'id_alumni',
        'id_lowongan',
        'status',
        'tanggal_apply',
        'tanggal_respon',
        'catatan',
        'catatan_admin',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_apply' => 'datetime',
            'tanggal_respon' => 'datetime',
        ];
    }

    // =====================
    // RELATIONSHIPS
    // =====================

    public function alumni()
    {
        return $this->belongsTo(Alumni::class, 'id_alumni', 'id_alumni');
    }

    public function lowongan()
    {
        return $this->belongsTo(Lowongan::class, 'id_lowongan', 'id_lowongan');
    }

    // =====================
    // SCOPES
    // =====================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDiterima($query)
    {
        return $query->where('status', 'diterima');
    }

    public function scopeDitolak($query)
    {
        return $query->where('status', 'ditolak');
    }

    public function scopeForAlumni($query, int $alumniId)
    {
        return $query->where('id_alumni', $alumniId);
    }

    public function scopeForLowongan($query, int $lowonganId)
    {
        return $query->where('id_lowongan', $lowonganId);
    }
}
