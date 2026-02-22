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

    public function riwayatStatus()
    {
        return $this->hasMany(RiwayatStatus::class, 'id_status', 'id_status');
    }
}
