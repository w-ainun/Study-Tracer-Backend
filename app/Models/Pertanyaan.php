<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pertanyaan extends Model
{
    use HasFactory;

    protected $table = 'pertanyaan';
    protected $primaryKey = 'id_pertanyaan';

    protected $fillable = [
        'id_sectionques',
        'isi_pertanyaan',
        'status_pertanyaan',
    ];

    /**
     * Relasi ke Section Ques
     */
    public function sectionQues()
    {
        return $this->belongsTo(\App\Models\SectionQues::class, 'id_sectionques', 'id_sectionques');
    }

    /**
     * Relasi ke Opsi Jawaban
     */
    public function opsiJawaban()
    {
        return $this->hasMany(\App\Models\OpsiJawaban::class, 'id_pertanyaan', 'id_pertanyaan');
    }

    /**
     * Relasi ke Jawaban
     */
    public function jawaban()
    {
        return $this->hasMany(\App\Models\Jawaban::class, 'id_pertanyaan', 'id_pertanyaan');
    }
}
