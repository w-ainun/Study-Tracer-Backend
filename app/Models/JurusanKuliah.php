<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JurusanKuliah extends Model
{
    use HasFactory;

    protected $table = 'jurusan_kuliah';
    protected $primaryKey = 'id_jurusanKuliah';

    protected $fillable = [
        'nama_jurusan',
        'id_universitas',
    ];

    /**
     * Universitas tempat jurusan ini berada.
     */
    public function universitas()
    {
        return $this->belongsTo(Universitas::class, 'id_universitas', 'id_universitas');
    }

    /**
     * Record kuliah yang mengambil jurusan ini.
     */
    public function kuliah()
    {
        return $this->hasMany(Kuliah::class, 'id_jurusanKuliah', 'id_jurusanKuliah');
    }
}
