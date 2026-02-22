<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlumniSkill extends Model
{
    use HasFactory;

    protected $table = 'alumni_skills';
    protected $primaryKey = 'id_alumniSkills';

    protected $fillable = [
        'id_alumni',
        'id_skills',
    ];

    public function alumni()
    {
        return $this->belongsTo(Alumni::class, 'id_alumni', 'id_alumni');
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class, 'id_skills', 'id_skills');
    }
}
