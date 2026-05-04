<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiwayatKelulusan extends Model
{
    use HasFactory;

    protected $table = 'riwayat_kelulusan';
    protected $primaryKey = 'id_kelulusan';

    protected $fillable = [
        'nisn',
        'nama',
        'id_jurusan',
        'status_kelulusan',
        'tahun_lulus',
        'confirmed_by',
        'batch_id',
    ];

    protected function casts(): array
    {
        return [
            'tahun_lulus' => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────

    public function jurusan()
    {
        return $this->belongsTo(Jurusan::class, 'id_jurusan', 'id_jurusan');
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by', 'id_users');
    }
}
