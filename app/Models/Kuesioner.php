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
        'title',
        'deskripsi',
        'status',
        'tanggal_mulai',
        'tanggal_selesai',
        'tanggal_publikasi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'datetime',
            'tanggal_selesai' => 'datetime',
            'tanggal_publikasi' => 'date',
        ];
    }

    /**
     * Relasi ke Status Karir
     */
    public function statusKarir()
    {
        return $this->belongsTo(Status::class, 'id_status', 'id_status');
    }

    /**
     * Relasi ke Pertanyaan (one-to-many)
     */
    public function pertanyaan()
    {
        return $this->hasMany(Pertanyaan::class, 'id_kuesioner', 'id_kuesioner');
    }
}
