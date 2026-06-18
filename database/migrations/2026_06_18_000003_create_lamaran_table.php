<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lamaran', function (Blueprint $table) {
            $table->id('id_lamaran');
            $table->unsignedBigInteger('id_alumni');
            $table->unsignedBigInteger('id_lowongan');
            $table->enum('status', ['pending', 'diterima', 'ditolak'])->default('pending');
            $table->timestamp('tanggal_apply')->useCurrent();
            $table->timestamp('tanggal_respon')->nullable();
            $table->text('catatan')->nullable();
            $table->text('catatan_admin')->nullable();

            $table->foreign('id_alumni')->references('id_alumni')->on('alumni')->onDelete('cascade');
            $table->foreign('id_lowongan')->references('id_lowongan')->on('lowongan')->onDelete('cascade');
            $table->unique(['id_alumni', 'id_lowongan']);

            // Performance indexes
            $table->index('status');
            $table->index(['id_alumni', 'status']);
            $table->index(['id_lowongan', 'status']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lamaran');
    }
};
