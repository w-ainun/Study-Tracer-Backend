<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use HasFactory;

    protected $table = 'skills';
    protected $primaryKey = 'id_skills';

    protected $fillable = [
        'name_skills',
    ];

    public function alumni()
    {
        return $this->belongsToMany(Alumni::class, 'alumni_skills', 'id_skills', 'id_alumni');
    }
}
