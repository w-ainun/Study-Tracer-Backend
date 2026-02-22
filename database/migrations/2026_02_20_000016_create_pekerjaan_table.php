<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pekerjaan', function (Blueprint $table) {
            $table->id('id_pekerjaan');
            $table->string('posisi');
            $table->unsignedBigInteger('id_perusahaan');
            $table->unsignedBigInteger('id_riwayat');
            $table->foreign('id_perusahaan')->references('id_perusahaan')->on('perusahaan')->onDelete('cascade');
            $table->foreign('id_riwayat')->references('id_riwayat')->on('riwayat_status')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pekerjaan');
    }
};
