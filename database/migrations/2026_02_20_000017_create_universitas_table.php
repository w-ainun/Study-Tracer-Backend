<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('universitas', function (Blueprint $table) {
            $table->id('id_universitas');
            $table->string('nama_universitas');
            $table->unsignedBigInteger('id_jurusanKuliah');
            $table->enum('jalur_masuk', ['SNBP', 'SNBT', 'Mandiri', 'Beasiswa', 'lainnya']);
            $table->unsignedBigInteger('id_riwayat');
            $table->enum('jenjang', ['D3', 'D4', 'S1', 'S2', 'S3']);
            $table->foreign('id_jurusanKuliah')->references('id_jurusanKuliah')->on('jurusan_kuliah')->onDelete('cascade');
            $table->foreign('id_riwayat')->references('id_riwayat')->on('riwayat_status')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('universitas');
    }
};
