<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni', function (Blueprint $table) {
            $table->id('id_alumni');
            $table->string('nama_alumni');
            $table->string('nis')->nullable();
            $table->string('nisn')->nullable();
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            $table->date('tanggal_lahir')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->year('tahun_masuk')->nullable();
            $table->string('foto')->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_hp')->nullable();
            $table->unsignedBigInteger('id_jurusan');
            $table->date('tahun_lulus')->nullable();
            $table->unsignedBigInteger('id_users')->unique();
            $table->enum('status_create', ['pending', 'ok', 'rejected', "banned"])->default('pending');
            $table->foreign('id_jurusan')->references('id_jurusan')->on('jurusan')->onDelete('cascade');
            $table->foreign('id_users')->references('id_users')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni');
    }
};
