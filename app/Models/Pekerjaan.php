<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pekerjaan extends Model
{
    use HasFactory;

    protected $table = 'pekerjaan';
    protected $primaryKey = 'id_pekerjaan';

    protected $fillable = [
        'posisi',
        'id_perusahaan',
        'id_riwayat',
    ];

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class, 'id_perusahaan', 'id_perusahaan');
    }

    public function riwayatStatus()
    {
        return $this->belongsTo(RiwayatStatus::class, 'id_riwayat', 'id_riwayat');
    }

    public function lowongan()
    {
        return $this->hasMany(Lowongan::class, 'id_pekerjaan', 'id_pekerjaan');
    }
}
