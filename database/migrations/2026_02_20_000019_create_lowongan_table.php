<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lowongan', function (Blueprint $table) {
            $table->id('id_lowongan');
            $table->string('judul_lowongan');
            $table->text('deskripsi')->nullable();
            $table->enum('status', ['draft', 'published', 'closed'])->default('draft');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->time('lowongan_selesai')->nullable();
            $table->unsignedBigInteger('id_pekerjaan')->nullable();
            $table->string('foto_lowongan')->nullable();
            $table->unsignedBigInteger('id_perusahaan');
            $table->foreign('id_pekerjaan')->references('id_pekerjaan')->on('pekerjaan')->onDelete('set null');
            $table->foreign('id_perusahaan')->references('id_perusahaan')->on('perusahaan')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lowongan');
    }
};
