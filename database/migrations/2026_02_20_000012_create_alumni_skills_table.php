<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni_skills', function (Blueprint $table) {
            $table->id('id_alumniSkills');
            $table->unsignedBigInteger('id_alumni');
            $table->unsignedBigInteger('id_skills');
            $table->foreign('id_alumni')->references('id_alumni')->on('alumni')->onDelete('cascade');
            $table->foreign('id_skills')->references('id_skills')->on('skills')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_skills');
    }
};
