<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurusan_kuliah', function (Blueprint $table) {
            $table->id('id_jurusanKuliah');
            $table->string('nama_jurusan');
            $table->unsignedBigInteger('id_universitas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurusan_kuliah');
    }
};
