<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deskripsi_karier', function (Blueprint $table) {
            $table->id('id_deskripsi');
            $table->unsignedBigInteger('id_riwayat');
            $table->text('deskripsi');
            $table->timestamps();

            $table->foreign('id_riwayat')->references('id_riwayat')->on('riwayat_status')->onDelete('cascade');
            $table->index('id_riwayat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deskripsi_karier');
    }
};
