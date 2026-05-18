<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Universitas extends Model
{
    use HasFactory;

    protected $table = 'universitas';
    protected $primaryKey = 'id_universitas';

    protected $fillable = [
        'nama_universitas',
        'alamat',
        'id_kota',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function kota()
    {
        return $this->belongsTo(Kota::class, 'id_kota', 'id_kota');
    }

    /**
     * Jurusan kuliah yang tersedia di universitas ini.
     */
    public function jurusanKuliah()
    {
        return $this->hasMany(JurusanKuliah::class, 'id_universitas', 'id_universitas');
    }

    /**
     * Record kuliah yang mereferensi universitas ini.
     */
    public function kuliah()
    {
        return $this->hasMany(Kuliah::class, 'id_universitas', 'id_universitas');
    }
}
