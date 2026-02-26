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
    ];

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
