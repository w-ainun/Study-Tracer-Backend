<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create kuliah table (career record for alumni dengan status "Kuliah")
        Schema::create('kuliah', function (Blueprint $table) {
            $table->id('id_kuliah');
            $table->unsignedBigInteger('id_universitas');
            $table->unsignedBigInteger('id_jurusanKuliah');
            $table->enum('jalur_masuk', ['SNBP', 'SNBT', 'Mandiri', 'Beasiswa', 'lainnya']);
            $table->enum('jenjang', ['D3', 'D4', 'S1', 'S2', 'S3']);
            $table->unsignedBigInteger('id_riwayat');

            $table->foreign('id_universitas')->references('id_universitas')->on('universitas')->onDelete('cascade');
            $table->foreign('id_jurusanKuliah')->references('id_jurusanKuliah')->on('jurusan_kuliah')->onDelete('cascade');
            $table->foreign('id_riwayat')->references('id_riwayat')->on('riwayat_status')->onDelete('cascade');
            $table->timestamps();
        });

        // Add FK constraint for jurusan_kuliah -> universitas
        // (jurusan_kuliah is created at 000003 before universitas at 000017, so FK added here)
        Schema::table('jurusan_kuliah', function (Blueprint $table) {
            $table->foreign('id_universitas')->references('id_universitas')->on('universitas')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('jurusan_kuliah', function (Blueprint $table) {
            $table->dropForeign(['id_universitas']);
        });

        Schema::dropIfExists('kuliah');
    }
};
