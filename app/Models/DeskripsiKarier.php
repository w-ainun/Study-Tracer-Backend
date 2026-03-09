<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeskripsiKarier extends Model
{
    protected $table = 'deskripsi_karier';
    protected $primaryKey = 'id_deskripsi';

    protected $fillable = [
        'id_riwayat',
        'deskripsi',
    ];

    /**
     * Relationship: Deskripsi belongs to RiwayatStatus
     */
    public function riwayatStatus(): BelongsTo
    {
        return $this->belongsTo(RiwayatStatus::class, 'id_riwayat', 'id_riwayat');
    }
}
