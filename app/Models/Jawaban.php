<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jawaban extends Model
{
    use HasFactory;

    protected $table = 'jawaban';
    protected $primaryKey = 'id_jawaban';

    protected $fillable = [
        'id_pertanyaan',
        'id_user',
        'id_opsiJawaban',
        'jawaban',
    ];

    /**
     * Relasi ke User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_users');
    }

    /**
     * Relasi ke Pertanyaan
     */
    public function pertanyaan()
    {
        return $this->belongsTo(Pertanyaan::class, 'id_pertanyaan', 'id_pertanyaan');
    }

    /**
     * Relasi ke Opsi Jawaban
     */
    public function opsiJawaban()
    {
        return $this->belongsTo(OpsiJawaban::class, 'id_opsiJawaban', 'id_opsi');
    }
}
