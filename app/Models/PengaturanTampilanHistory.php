<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengaturanTampilanHistory extends Model
{
    public $timestamps = false;

    protected $table = 'pengaturan_tampilan_history';

    protected $fillable = [
        'pengaturan_tampilan_id',
        'snapshot',
        'changed_by',
        'change_type',
        'created_at',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * The settings record this history belongs to.
     */
    public function pengaturanTampilan(): BelongsTo
    {
        return $this->belongsTo(PengaturanTampilan::class, 'pengaturan_tampilan_id');
    }

    /**
     * The admin who made this change.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
