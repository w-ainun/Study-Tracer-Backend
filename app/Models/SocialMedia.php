<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialMedia extends Model
{
    use HasFactory;

    protected $table = 'social_media';
    protected $primaryKey = 'id_sosmed';

    protected $fillable = [
        'nama_sosmed',
        'icon_sosmed',
    ];

    public function alumni()
    {
        return $this->belongsToMany(Alumni::class, 'alumni_social_media', 'id_sosmed', 'id_alumni');
    }
}
