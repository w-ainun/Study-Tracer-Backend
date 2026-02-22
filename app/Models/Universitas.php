<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Universitas extends Model
{
    use HasFactory;

    protected $table = 'universitas';
    protected $primaryKey = 'id_universitas';

    protected $fillable = [
        'nama_universitas',
        'id_jurusanKuliah',
        'jalur_masuk',
        'id_riwayat',
        'jenjang',
    ];

    public function jurusanKuliah()
    {
        return $this->belongsTo(JurusanKuliah::class, 'id_jurusanKuliah', 'id_jurusanKuliah');
    }

    public function riwayatStatus()
    {
        return $this->belongsTo(RiwayatStatus::class, 'id_riwayat', 'id_riwayat');
    }
}
