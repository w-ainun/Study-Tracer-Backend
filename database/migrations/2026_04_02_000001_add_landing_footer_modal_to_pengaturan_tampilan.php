<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add landing_bg, footer content, and modal text fields
     * to the pengaturan_tampilan table.
     */
    public function up(): void
    {
        Schema::table('pengaturan_tampilan', function (Blueprint $table) {
            // Landing page hero background image
            $table->string('landing_bg', 500)->nullable()->after('login_bg');

            // Footer content
            $table->text('deskripsi_footer')->nullable()->after('third_color');
            $table->string('email_kontak', 255)->nullable()->after('deskripsi_footer');
            $table->string('web_kontak', 255)->nullable()->after('email_kontak');
            $table->string('telp_kontak', 50)->nullable()->after('web_kontak');

            // Modal text content (privacy, terms, support)
            $table->longText('teks_privasi')->nullable()->after('telp_kontak');
            $table->longText('teks_layanan')->nullable()->after('teks_privasi');
            $table->text('teks_dukungan')->nullable()->after('teks_layanan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengaturan_tampilan', function (Blueprint $table) {
            $table->dropColumn([
                'landing_bg',
                'deskripsi_footer',
                'email_kontak',
                'web_kontak',
                'telp_kontak',
                'teks_privasi',
                'teks_layanan',
                'teks_dukungan',
            ]);
        });
    }
};
