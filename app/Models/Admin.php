<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    use HasFactory;

    protected $table = 'admin';
    protected $primaryKey = 'id_admin';

    protected $fillable = [
        'nama_admin',
        'id_users',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_users', 'id_users');
    }
}
