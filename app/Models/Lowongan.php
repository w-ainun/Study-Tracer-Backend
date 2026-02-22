<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lowongan extends Model
{
    use HasFactory;

    protected $table = 'lowongan';
    protected $primaryKey = 'id_lowongan';

    protected $fillable = [
        'judul_lowongan',
        'deskripsi',
        'status',
        'approval_status',
        'lowongan_selesai',
        'id_pekerjaan',
        'foto_lowongan',
        'id_perusahaan',
    ];

    public function pekerjaan()
    {
        return $this->belongsTo(Pekerjaan::class, 'id_pekerjaan', 'id_pekerjaan');
    }

    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class, 'id_perusahaan', 'id_perusahaan');
    }

    public function simpanLowongan()
    {
        return $this->hasMany(SimpanLowongan::class, 'id_lowongan', 'id_lowongan');
    }
}
