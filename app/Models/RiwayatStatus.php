<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiwayatStatus extends Model
{
    use HasFactory;

    protected $table = 'riwayat_status';
    protected $primaryKey = 'id_riwayat';

    protected $fillable = [
        'id_alumni',
        'id_status',
        'tahun_mulai',
        'tahun_selesai',
        'approval_status',
    ];

    public function alumni()
    {
        return $this->belongsTo(Alumni::class, 'id_alumni', 'id_alumni');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'id_status', 'id_status');
    }

    public function pekerjaan()
    {
        return $this->hasOne(Pekerjaan::class, 'id_riwayat', 'id_riwayat');
    }

    public function kuliah()
    {
        return $this->hasOne(Kuliah::class, 'id_riwayat', 'id_riwayat');
    }

    public function wirausaha()
    {
        return $this->hasOne(Wirausaha::class, 'id_riwayat', 'id_riwayat');
    }

    public function deskripsiKarier()
    {
        return $this->hasOne(DeskripsiKarier::class, 'id_riwayat', 'id_riwayat');
    }
}
