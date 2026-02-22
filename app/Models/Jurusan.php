<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jurusan extends Model
{
    use HasFactory;

    protected $table = 'jurusan';
    protected $primaryKey = 'id_jurusan';

    protected $fillable = [
        'nama_jurusan',
    ];

    public function alumni()
    {
        return $this->hasMany(Alumni::class, 'id_jurusan', 'id_jurusan');
    }
}
