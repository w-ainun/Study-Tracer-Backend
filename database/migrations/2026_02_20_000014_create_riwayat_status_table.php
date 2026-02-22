<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_status', function (Blueprint $table) {
            $table->id('id_riwayat');
            $table->unsignedBigInteger('id_alumni');
            $table->unsignedBigInteger('id_status');
            $table->year('tahun_mulai')->nullable();
            $table->year('tahun_selesai')->nullable();
            $table->foreign('id_alumni')->references('id_alumni')->on('alumni')->onDelete('cascade');
            $table->foreign('id_status')->references('id_status')->on('status')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_status');
    }
};
