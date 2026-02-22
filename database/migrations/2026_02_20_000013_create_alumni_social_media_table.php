<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni_social_media', function (Blueprint $table) {
            $table->id('id_alumniSosmed');
            $table->unsignedBigInteger('id_alumni');
            $table->unsignedBigInteger('id_sosmed');
            $table->string('url');
            $table->timestamp('create_at')->nullable();
            $table->foreign('id_alumni')->references('id_alumni')->on('alumni')->onDelete('cascade');
            $table->foreign('id_sosmed')->references('id_sosmed')->on('social_media')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_social_media');
    }
};
