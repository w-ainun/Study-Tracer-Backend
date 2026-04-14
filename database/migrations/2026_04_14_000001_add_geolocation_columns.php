<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom latitude & longitude ke tabel-tabel yang membutuhkan
     * data koordinat untuk fitur Sebaran Alumni (Mapping).
     */
    public function up(): void
    {
        // Kota — koordinat pusat kota
        Schema::table('kota', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('id_provinsi');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->index(['latitude', 'longitude'], 'kota_geo_index');
        });

        // Provinsi — koordinat pusat provinsi
        Schema::table('provinsi', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('code');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        // Perusahaan — lokasi perusahaan
        Schema::table('perusahaan', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('jalan');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->index(['latitude', 'longitude'], 'perusahaan_geo_index');
        });

        // Universitas — lokasi kampus
        Schema::table('universitas', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('nama_universitas');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->index(['latitude', 'longitude'], 'universitas_geo_index');
        });

        // Wirausaha — lokasi usaha
        Schema::table('wirausaha', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('nama_usaha');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->index(['latitude', 'longitude'], 'wirausaha_geo_index');
        });
    }

    public function down(): void
    {
        Schema::table('kota', function (Blueprint $table) {
            $table->dropIndex('kota_geo_index');
            $table->dropColumn(['latitude', 'longitude']);
        });

        Schema::table('provinsi', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });

        Schema::table('perusahaan', function (Blueprint $table) {
            $table->dropIndex('perusahaan_geo_index');
            $table->dropColumn(['latitude', 'longitude']);
        });

        Schema::table('universitas', function (Blueprint $table) {
            $table->dropIndex('universitas_geo_index');
            $table->dropColumn(['latitude', 'longitude']);
        });

        Schema::table('wirausaha', function (Blueprint $table) {
            $table->dropIndex('wirausaha_geo_index');
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
