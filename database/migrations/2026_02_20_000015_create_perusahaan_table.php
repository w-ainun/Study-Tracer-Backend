<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perusahaan', function (Blueprint $table) {
            $table->id('id_perusahaan');
            $table->string('nama_perusahaan');
            $table->unsignedBigInteger('id_kota');
            $table->string('jalan');
            $table->foreign('id_kota')->references('id_kota')->on('kota')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perusahaan');
    }
};
