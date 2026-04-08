<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PengaturanTampilan extends Model
{
    protected $table = 'pengaturan_tampilan';

    /**
     * Factory default values used for reset-to-default.
     * These match the original migration seed values.
     */
    public const FACTORY_DEFAULTS = [
        'nama_sekolah'       => 'SMK Negeri 1 Gondang',
        'logo'               => null,
        'login_bg'           => null,
        'landing_bg'         => null,
        'landing_title'      => null,
        'landing_description'=> null,
        'primary_color'      => '#3C5759',
        'secondary_color'    => '#F3F4F4',
        'third_color'        => '#9CA3AF',
        'deskripsi_footer'   => null,
        'email_kontak'       => null,
        'web_kontak'         => null,
        'telp_kontak'        => null,
        'teks_privasi'       => null,
        'teks_layanan'       => null,
        'teks_dukungan'      => null,
    ];

    /**
     * Fields that are snapshotable (saved to history before changes).
     */
    public const SNAPSHOTABLE_FIELDS = [
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

    /**
     * Get a snapshot of current settings for history storage.
     */
    public function toSnapshot(): array
    {
        return $this->only(self::SNAPSHOTABLE_FIELDS);
    }

    /**
     * History of changes (snapshots before each update).
     */
    public function histories(): HasMany
    {
        return $this->hasMany(PengaturanTampilanHistory::class, 'pengaturan_tampilan_id')
                    ->orderByDesc('created_at');
    }
}
