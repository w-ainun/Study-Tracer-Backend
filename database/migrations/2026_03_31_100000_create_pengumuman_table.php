<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengumuman', function (Blueprint $table) {
            $table->id('id_pengumuman');
            $table->string('judul', 255);
            $table->longText('konten');
            $table->string('foto', 255)->nullable();
            $table->enum('status', ['aktif', 'draft', 'berakhir'])->default('draft');
            $table->boolean('is_pinned')->default(false);
            $table->unsignedBigInteger('id_users');
            $table->timestamps();

            // Foreign key
            $table->foreign('id_users')->references('id_users')->on('users')->onDelete('cascade');

            // Performance indexes
            $table->index('status');
            $table->index('is_pinned');
            $table->index('created_at');
            $table->index(['status', 'is_pinned', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengumuman');
    }
};
