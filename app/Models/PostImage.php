<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostImage extends Model
{
    use HasFactory;

    protected $table = 'post_images';
    protected $primaryKey = 'id_post_image';

    protected $fillable = [
        'id_post',
        'image_path',
        'sort_order',
    ];

    // =====================
    // RELATIONSHIPS
    // =====================

    public function post()
    {
        return $this->belongsTo(Post::class, 'id_post', 'id_post');
    }
}
