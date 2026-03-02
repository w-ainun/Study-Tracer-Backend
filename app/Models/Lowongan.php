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
        'tipe_pekerjaan',
        'lokasi',
        'status',
        'approval_status',
        'lowongan_selesai',
        'jam_mulai',
        'jam_berakhir',
        'id_pekerjaan',
        'foto_lowongan',
        'id_perusahaan',
        'id_users',
    ];

    protected $casts = [
        'lowongan_selesai' => 'date',
        'jam_mulai' => 'string',
        'jam_berakhir' => 'string',
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

    public function user()
    {
        return $this->belongsTo(User::class, 'id_users', 'id_users');
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'lowongan_skills', 'id_lowongan', 'id_skills')
            ->withTimestamps();
    }
}
