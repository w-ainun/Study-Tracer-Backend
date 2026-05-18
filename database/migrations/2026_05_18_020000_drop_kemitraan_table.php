<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the kemitraan table (feature fully removed).
     */
    public function up(): void
    {
        Schema::dropIfExists('kemitraan');
    }

    /**
     * Re-create the kemitraan table (rollback).
     */
    public function down(): void
    {
        Schema::create('kemitraan', function (Blueprint $table) {
            $table->id('id_kemitraan');
            $table->enum('tipe', ['universitas', 'perusahaan']);
            $table->string('nama');
            $table->string('logo')->nullable();
            $table->unsignedBigInteger('id_universitas')->nullable();
            $table->unsignedBigInteger('id_perusahaan')->nullable();
            $table->timestamps();

            $table->foreign('id_universitas')
                ->references('id_universitas')
                ->on('universitas')
                ->onDelete('set null');

            $table->foreign('id_perusahaan')
                ->references('id_perusahaan')
                ->on('perusahaan')
                ->onDelete('set null');
        });
    }
};
