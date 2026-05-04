<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalonLulusan extends Model
{
    use HasFactory;

    protected $table = 'calon_lulusan';
    protected $primaryKey = 'id_calon';

    protected $fillable = [
        'nisn',
        'nama',
        'id_jurusan',
        'status_kelulusan',
        'imported_by',
        'batch_id',
    ];

    // ── Relationships ────────────────────────────

    public function jurusan()
    {
        return $this->belongsTo(Jurusan::class, 'id_jurusan', 'id_jurusan');
    }

    public function importer()
    {
        return $this->belongsTo(User::class, 'imported_by', 'id_users');
    }
}
