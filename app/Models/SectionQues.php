<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SectionQues extends Model
{
    use HasFactory;

    protected $table = 'section_ques';
    protected $primaryKey = 'id_sectionques';

    protected $fillable = [
        'id_kuesioner',
        'judul_pertanyaan',
    ];

    /**
     * Relasi ke Kuesioner
     */
    public function kuesioner()
    {
        return $this->belongsTo(Kuesioner::class, 'id_kuesioner', 'id_kuesioner');
    }

    /**
     * Relasi ke Pertanyaan
     */
    public function pertanyaan()
    {
        return $this->hasMany(Pertanyaan::class, 'id_sectionques', 'id_sectionques');
    }
}
