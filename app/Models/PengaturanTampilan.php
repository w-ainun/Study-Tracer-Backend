<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengaturanTampilan extends Model
{
    protected $table = 'pengaturan_tampilan';

    protected $fillable = [
        'nama_sekolah',
        'logo',
        'login_bg',
        'primary_color',
        'secondary_color',
        'third_color',
    ];
}
