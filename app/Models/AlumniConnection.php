<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlumniConnection extends Model
{
    use HasFactory;

    protected $table = 'alumni_connections';
    protected $primaryKey = 'id_connection';

    protected $fillable = [
        'id_alumni_requester',
        'id_alumni_addressee',
        'status',
        'accepted_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    // =====================
    // RELATIONSHIPS
    // =====================

    /**
     * Alumni yang mengirim permintaan koneksi.
     */
    public function requester()
    {
        return $this->belongsTo(Alumni::class, 'id_alumni_requester', 'id_alumni');
    }

    /**
     * Alumni yang menerima permintaan koneksi.
     */
    public function addressee()
    {
        return $this->belongsTo(Alumni::class, 'id_alumni_addressee', 'id_alumni');
    }

    // =====================
    // SCOPES
    // =====================

    /**
     * Scope: hanya koneksi yang sudah accepted.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope: hanya koneksi yang masih pending.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: hanya koneksi yang rejected.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope: koneksi yang melibatkan alumni tertentu (sebagai requester atau addressee).
     */
    public function scopeInvolvingAlumni($query, int $alumniId)
    {
        return $query->where(function ($q) use ($alumniId) {
            $q->where('id_alumni_requester', $alumniId)
              ->orWhere('id_alumni_addressee', $alumniId);
        });
    }

    // =====================
    // HELPER METHODS
    // =====================

    /**
     * Terima permintaan koneksi.
     */
    public function accept()
    {
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
            'rejected_at' => null,
        ]);
    }

    /**
     * Tolak permintaan koneksi.
     */
    public function reject()
    {
        $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
        ]);
    }

    /**
     * Cek apakah koneksi sudah diterima.
     */
    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    /**
     * Cek apakah koneksi masih pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
