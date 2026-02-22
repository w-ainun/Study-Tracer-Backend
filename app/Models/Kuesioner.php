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
        'judul_kuesioner',
        'deskripsi_kuesioner',
        'status_kuesioner',
        'tanggal_publikasi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_publikasi' => 'date',
        ];
    }

    public function pertanyaan()
    {
        return $this->hasMany(PertanyaanKuesioner::class, 'id_kuesioner', 'id_kuesioner');
    }
}
