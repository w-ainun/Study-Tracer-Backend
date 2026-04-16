<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah id_kota (FK) ke universitas dan wirausaha.
 * id_kota → kota → id_provinsi → provinsi, jadi otomatis dapat provinsi juga.
 * Perusahaan sudah punya id_kota sejak awal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('universitas', function (Blueprint $table) {
            $table->unsignedBigInteger('id_kota')->nullable()->after('alamat');
            $table->foreign('id_kota')
                  ->references('id_kota')
                  ->on('kota')
                  ->onDelete('set null');
        });

        Schema::table('wirausaha', function (Blueprint $table) {
            $table->unsignedBigInteger('id_kota')->nullable()->after('alamat');
            $table->foreign('id_kota')
                  ->references('id_kota')
                  ->on('kota')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('universitas', function (Blueprint $table) {
            $table->dropForeign(['id_kota']);
            $table->dropColumn('id_kota');
        });

        Schema::table('wirausaha', function (Blueprint $table) {
            $table->dropForeign(['id_kota']);
            $table->dropColumn('id_kota');
        });
    }
};
