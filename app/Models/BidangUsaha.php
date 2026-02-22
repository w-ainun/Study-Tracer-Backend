<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BidangUsaha extends Model
{
    use HasFactory;

    protected $table = 'bidang_usaha';
    protected $primaryKey = 'id_bidang';

    protected $fillable = [
        'nama_bidang',
    ];

    public function wirausaha()
    {
        return $this->hasMany(Wirausaha::class, 'id_bidang', 'id_bidang');
    }
}
