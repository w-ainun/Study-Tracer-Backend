<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alumni extends Model
{
    use HasFactory;

    protected $table = 'alumni';
    protected $primaryKey = 'id_alumni';

    protected $fillable = [
        'nama_alumni',
        'nis',
        'nisn',
        'jenis_kelamin',
        'tanggal_lahir',
        'tempat_lahir',
        'tahun_masuk',
        'foto',
        'alamat',
        'no_hp',
        'id_jurusan',
        'tahun_lulus',
        'id_users',
        'status_create',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tahun_lulus' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_users', 'id_users');
    }

    public function jurusan()
    {
        return $this->belongsTo(Jurusan::class, 'id_jurusan', 'id_jurusan');
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'alumni_skills', 'id_alumni', 'id_skills');
    }

    public function socialMedia()
    {
        return $this->belongsToMany(SocialMedia::class, 'alumni_social_media', 'id_alumni', 'id_sosmed')
            ->withPivot('url', 'create_at');
    }

    public function riwayatStatus()
    {
        return $this->hasMany(RiwayatStatus::class, 'id_alumni', 'id_alumni');
    }
}
