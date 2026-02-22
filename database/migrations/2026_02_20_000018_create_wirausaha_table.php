<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wirausaha', function (Blueprint $table) {
            $table->id('id_wirausaha');
            $table->unsignedBigInteger('id_bidang');
            $table->string('nama_usaha');
            $table->unsignedBigInteger('id_riwayat');
            $table->foreign('id_bidang')->references('id_bidang')->on('bidang_usaha')->onDelete('cascade');
            $table->foreign('id_riwayat')->references('id_riwayat')->on('riwayat_status')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wirausaha');
    }
};
