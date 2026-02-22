<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simpan_lowongan', function (Blueprint $table) {
            $table->id('id_simpan');
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_lowongan');
            $table->foreign('id_user')->references('id_users')->on('users')->onDelete('cascade');
            $table->foreign('id_lowongan')->references('id_lowongan')->on('lowongan')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simpan_lowongan');
    }
};
