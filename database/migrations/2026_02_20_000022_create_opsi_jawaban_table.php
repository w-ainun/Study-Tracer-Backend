<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opsi_jawaban', function (Blueprint $table) {
            $table->id('id_opsi');
            $table->unsignedBigInteger('id_pertanyaan');
            $table->text('opsi');
            $table->foreign('id_pertanyaan')->references('id_pertanyaanKuis')->on('pertanyaan_kuesioner')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opsi_jawaban');
    }
};
