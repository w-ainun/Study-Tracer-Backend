<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Perusahaan extends Model
{
    use HasFactory;

    protected $table = 'perusahaan';
    protected $primaryKey = 'id_perusahaan';

    protected $fillable = [
        'nama_perusahaan',
        'id_kota',
        'jalan',
    ];

    public function kota()
    {
        return $this->belongsTo(Kota::class, 'id_kota', 'id_kota');
    }

    public function pekerjaan()
    {
        return $this->hasMany(Pekerjaan::class, 'id_perusahaan', 'id_perusahaan');
    }

    public function lowongan()
    {
        return $this->hasMany(Lowongan::class, 'id_perusahaan', 'id_perusahaan');
    }
}
