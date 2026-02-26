<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpsiJawaban extends Model
{
    use HasFactory;

    protected $table = 'opsi_jawaban';
    protected $primaryKey = 'id_opsi';

    protected $fillable = [
        'id_pertanyaan',
        'opsi',
    ];

    /**
     * Relasi ke Pertanyaan
     */
    public function pertanyaan()
    {
        return $this->belongsTo(Pertanyaan::class, 'id_pertanyaan', 'id_pertanyaan');
    }

    /**
     * Relasi ke Jawaban
     */
    public function jawaban()
    {
        return $this->hasMany(Jawaban::class, 'id_opsiJawaban', 'id_opsi');
    }
}
