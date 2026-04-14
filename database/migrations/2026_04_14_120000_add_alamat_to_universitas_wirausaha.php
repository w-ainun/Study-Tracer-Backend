<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom alamat ke universitas dan wirausaha.
 * Perusahaan sudah punya 'jalan' sebagai alamat.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Universitas — tambah alamat kampus
        Schema::table('universitas', function (Blueprint $table) {
            $table->string('alamat')->nullable()->after('nama_universitas');
        });

        // Wirausaha — tambah alamat usaha
        Schema::table('wirausaha', function (Blueprint $table) {
            $table->string('alamat')->nullable()->after('nama_usaha');
        });
    }

    public function down(): void
    {
        Schema::table('universitas', function (Blueprint $table) {
            $table->dropColumn('alamat');
        });

        Schema::table('wirausaha', function (Blueprint $table) {
            $table->dropColumn('alamat');
        });
    }
};
