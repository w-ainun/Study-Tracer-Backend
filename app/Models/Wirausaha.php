<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wirausaha extends Model
{
    use HasFactory;

    protected $table = 'wirausaha';
    protected $primaryKey = 'id_wirausaha';

    protected $fillable = [
        'id_bidang',
        'nama_usaha',
        'id_riwayat',
    ];

    public function bidangUsaha()
    {
        return $this->belongsTo(BidangUsaha::class, 'id_bidang', 'id_bidang');
    }

    public function riwayatStatus()
    {
        return $this->belongsTo(RiwayatStatus::class, 'id_riwayat', 'id_riwayat');
    }
}
