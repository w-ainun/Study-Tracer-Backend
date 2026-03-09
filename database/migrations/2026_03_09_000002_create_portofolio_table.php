<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portofolio', function (Blueprint $table) {
            $table->id('id_portofolio');
            $table->unsignedBigInteger('id_alumni');
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('link_project')->nullable();
            $table->string('gambar')->nullable(); // Path to image
            $table->timestamps();

            $table->foreign('id_alumni')->references('id_alumni')->on('alumni')->onDelete('cascade');
            $table->index('id_alumni');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portofolio');
    }
};
