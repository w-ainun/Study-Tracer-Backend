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
        'landing_bg',
        'landing_title',
        'landing_description',
        'primary_color',
        'secondary_color',
        'third_color',
        'deskripsi_footer',
        'email_kontak',
        'web_kontak',
        'telp_kontak',
        'teks_privasi',
        'teks_layanan',
        'teks_dukungan',
    ];
}
