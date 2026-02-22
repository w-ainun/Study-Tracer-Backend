<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pertanyaan_kuesioner', function (Blueprint $table) {
            $table->id('id_pertanyaanKuis');
            $table->unsignedBigInteger('id_kuesioner');
            $table->text('pertanyaan');
            $table->foreign('id_kuesioner')->references('id_kuesioner')->on('kuesioner')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pertanyaan_kuesioner');
    }
};
