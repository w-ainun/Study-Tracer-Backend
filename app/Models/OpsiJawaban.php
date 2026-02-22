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

    public function pertanyaan()
    {
        return $this->belongsTo(PertanyaanKuesioner::class, 'id_pertanyaan', 'id_pertanyaanKuis');
    }

    public function jawaban()
    {
        return $this->hasMany(JawabanKuesioner::class, 'id_opsiJawaban', 'id_opsi');
    }
}
