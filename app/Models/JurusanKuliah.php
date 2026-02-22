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
    ];

    public function universitas()
    {
        return $this->hasMany(Universitas::class, 'id_jurusanKuliah', 'id_jurusanKuliah');
    }
}
