<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel pivot many-to-many antara lowongan dan skills
        Schema::create('lowongan_skills', function (Blueprint $table) {
            $table->id('id_lowongan_skills');
            $table->unsignedBigInteger('id_lowongan');
            $table->unsignedBigInteger('id_skills');
            $table->foreign('id_lowongan')->references('id_lowongan')->on('lowongan')->onDelete('cascade');
            $table->foreign('id_skills')->references('id_skills')->on('skills')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['id_lowongan', 'id_skills']);
        });

        // Tambah kolom jam_mulai dan jam_berakhir pada tabel lowongan
        Schema::table('lowongan', function (Blueprint $table) {
            $table->time('jam_mulai')->nullable()->after('lowongan_selesai');
            $table->time('jam_berakhir')->nullable()->after('jam_mulai');
        });
    }

    public function down(): void
    {
        Schema::table('lowongan', function (Blueprint $table) {
            $table->dropColumn(['jam_mulai', 'jam_berakhir']);
        });

        Schema::dropIfExists('lowongan_skills');
    }
};
