<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JawabanKuesioner extends Model
{
    use HasFactory;

    protected $table = 'jawaban_kuesioner';
    protected $primaryKey = 'id_jawabanKuis';

    protected $fillable = [
        'id_pertanyaan',
        'id_user',
        'id_opsiJawaban',
        'jawaban',
    ];

    public function pertanyaan()
    {
        return $this->belongsTo(PertanyaanKuesioner::class, 'id_pertanyaan', 'id_pertanyaanKuis');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_users');
    }

    public function opsiJawaban()
    {
        return $this->belongsTo(OpsiJawaban::class, 'id_opsiJawaban', 'id_opsi');
    }
}
