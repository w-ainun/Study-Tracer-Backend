<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jawaban_kuesioner', function (Blueprint $table) {
            $table->id('id_jawabanKuis');
            $table->unsignedBigInteger('id_pertanyaan');
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_opsiJawaban')->nullable();
            $table->enum('status', ['Selesai', 'Belum Selesai']);
            $table->text('jawaban')->nullable();
            $table->foreign('id_pertanyaan')->references('id_pertanyaanKuis')->on('pertanyaan_kuesioner')->onDelete('cascade');
            $table->foreign('id_user')->references('id_users')->on('users')->onDelete('cascade');
            $table->foreign('id_opsiJawaban')->references('id_opsi')->on('opsi_jawaban')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jawaban_kuesioner');
    }
};
