<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kuliah extends Model
{
    use HasFactory;

    protected $table = 'kuliah';
    protected $primaryKey = 'id_kuliah';

    protected $fillable = [
        'id_universitas',
        'id_jurusanKuliah',
        'jalur_masuk',
        'jenjang',
        'id_riwayat',
    ];

    protected $casts = [
        'jalur_masuk' => 'string',
        'jenjang' => 'string',
    ];

    public function universitas()
    {
        return $this->belongsTo(Universitas::class, 'id_universitas', 'id_universitas');
    }

    public function jurusanKuliah()
    {
        return $this->belongsTo(JurusanKuliah::class, 'id_jurusanKuliah', 'id_jurusanKuliah');
    }

    public function riwayatStatus()
    {
        return $this->belongsTo(RiwayatStatus::class, 'id_riwayat', 'id_riwayat');
    }
}
