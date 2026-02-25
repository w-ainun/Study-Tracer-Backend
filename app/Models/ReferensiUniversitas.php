<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferensiUniversitas extends Model
{
    use HasFactory;

    protected $table = 'referensi_universitas';
    protected $primaryKey = 'id_ref_univ';

    protected $fillable = [
        'nama_universitas',
        'jurusan',
    ];

    protected function casts(): array
    {
        return [
            'jurusan' => 'array',
        ];
    }
}
