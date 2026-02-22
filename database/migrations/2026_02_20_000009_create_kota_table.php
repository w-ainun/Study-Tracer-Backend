<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kota', function (Blueprint $table) {
            $table->id('id_kota');
            $table->string('nama_kota');
            $table->unsignedBigInteger('id_provinsi');
            $table->foreign('id_provinsi')->references('id_provinsi')->on('provinsi')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kota');
    }
};
