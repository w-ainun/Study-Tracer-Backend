<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PertanyaanKuesioner extends Model
{
    use HasFactory;

    protected $table = 'pertanyaan_kuesioner';
    protected $primaryKey = 'id_pertanyaanKuis';

    protected $fillable = [
        'id_kuesioner',
        'pertanyaan',
    ];

    public function kuesioner()
    {
        return $this->belongsTo(Kuesioner::class, 'id_kuesioner', 'id_kuesioner');
    }

    public function opsiJawaban()
    {
        return $this->hasMany(OpsiJawaban::class, 'id_pertanyaan', 'id_pertanyaanKuis');
    }

    public function jawaban()
    {
        return $this->hasMany(JawabanKuesioner::class, 'id_pertanyaan', 'id_pertanyaanKuis');
    }
}
