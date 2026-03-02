<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kuesioner', function (Blueprint $table) {
            $table->id('id_kuesioner');
            $table->foreignId('id_status')->nullable()->constrained('status', 'id_status')->onDelete('set null');
            $table->string('title');
            $table->text('deskripsi')->nullable();
            $table->enum('status', ['hidden', 'aktif', 'draft'])->default('draft');
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->date('tanggal_publikasi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kuesioner');
    }
};
