<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimpanLowongan extends Model
{
    use HasFactory;

    protected $table = 'simpan_lowongan';
    protected $primaryKey = 'id_simpan';

    protected $fillable = [
        'id_user',
        'id_lowongan',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_users');
    }

    public function lowongan()
    {
        return $this->belongsTo(Lowongan::class, 'id_lowongan', 'id_lowongan');
    }
}
