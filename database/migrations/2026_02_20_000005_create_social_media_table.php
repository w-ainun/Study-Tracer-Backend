<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_media', function (Blueprint $table) {
            $table->id('id_sosmed');
            $table->string('nama_sosmed');
            $table->string('icon_sosmed');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_media');
    }
};
