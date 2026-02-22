<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlumniSocialMedia extends Model
{
    use HasFactory;

    protected $table = 'alumni_social_media';
    protected $primaryKey = 'id_alumniSosmed';

    protected $fillable = [
        'id_alumni',
        'id_sosmed',
        'url',
        'create_at',
    ];

    protected function casts(): array
    {
        return [
            'create_at' => 'datetime',
        ];
    }

    public function alumni()
    {
        return $this->belongsTo(Alumni::class, 'id_alumni', 'id_alumni');
    }

    public function socialMedia()
    {
        return $this->belongsTo(SocialMedia::class, 'id_sosmed', 'id_sosmed');
    }
}
