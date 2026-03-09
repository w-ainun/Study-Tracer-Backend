<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Portofolio extends Model
{
    protected $table = 'portofolio';
    protected $primaryKey = 'id_portofolio';

    protected $fillable = [
        'id_alumni',
        'judul',
        'deskripsi',
        'link_project',
        'gambar',
    ];

    /**
     * Relationship: Portofolio belongs to Alumni
     */
    public function alumni(): BelongsTo
    {
        return $this->belongsTo(Alumni::class, 'id_alumni', 'id_alumni');
    }
}
