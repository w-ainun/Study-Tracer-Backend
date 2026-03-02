<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class LowonganSkill extends Pivot
{
    protected $table = 'lowongan_skills';
    protected $primaryKey = 'id_lowongan_skills';

    protected $fillable = [
        'id_lowongan',
        'id_skills',
    ];

    public function lowongan()
    {
        return $this->belongsTo(Lowongan::class, 'id_lowongan', 'id_lowongan');
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class, 'id_skills', 'id_skills');
    }
}
