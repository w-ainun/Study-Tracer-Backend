<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;

    protected $table = 'status';
    protected $primaryKey = 'id_status';

    protected $fillable = [
        'nama_status',
    ];

    /**
     * Relasi ke Riwayat Status
     */
    public function riwayatStatus()
    {
        return $this->hasMany(RiwayatStatus::class, 'id_status', 'id_status');
    }

    /**
     * Relasi ke Kuesioner
     */
    public function kuesioner()
    {
        return $this->hasMany(Kuesioner::class, 'id_status', 'id_status');
    }
}
