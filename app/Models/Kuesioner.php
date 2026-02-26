<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kuesioner extends Model
{
    use HasFactory;

    protected $table = 'kuesioner';
    protected $primaryKey = 'id_kuesioner';

    protected $fillable = [
        'id_status',
        'status_kuesioner',
        'tanggal_publikasi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_publikasi' => 'date',
        ];
    }

    /**
     * Relasi ke Status
     */
    public function status()
    {
        return $this->belongsTo(Status::class, 'id_status', 'id_status');
    }

    /**
     * Relasi ke Section Ques
     */
    public function sectionQues()
    {
        return $this->hasMany(SectionQues::class, 'id_kuesioner', 'id_kuesioner');
    }

    /**
     * Get all pertanyaan through section_ques
     */
    public function pertanyaan()
    {
        return $this->hasManyThrough(
            Pertanyaan::class,
            SectionQues::class,
            'id_kuesioner', // Foreign key on section_ques table
            'id_sectionques', // Foreign key on pertanyaan table
            'id_kuesioner', // Local key on kuesioner table
            'id_sectionques' // Local key on section_ques table
        );
    }
}
