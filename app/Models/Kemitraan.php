<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kemitraan extends Model
{
    use HasFactory;

    protected $table = 'kemitraan';
    protected $primaryKey = 'id_kemitraan';

    protected $fillable = [
        'tipe',
        'nama',
        'alamat',
        'logo',
        'id_universitas',
        'id_perusahaan',
    ];

    /**
     * Relasi ke Universitas (jika tipe = universitas).
     */
    public function universitas()
    {
        return $this->belongsTo(Universitas::class, 'id_universitas', 'id_universitas');
    }

    /**
     * Relasi ke Perusahaan (jika tipe = perusahaan).
     */
    public function perusahaan()
    {
        return $this->belongsTo(Perusahaan::class, 'id_perusahaan', 'id_perusahaan');
    }
}
