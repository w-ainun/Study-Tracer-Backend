<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the kemitraan (partnership) table.
     * Relates to both universitas and perusahaan via nullable FKs.
     */
    public function up(): void
    {
        Schema::create('kemitraan', function (Blueprint $table) {
            $table->id('id_kemitraan');
            $table->enum('tipe', ['universitas', 'perusahaan']);
            $table->string('nama');
            $table->string('alamat', 500)->nullable();
            $table->string('logo')->nullable();

            // Optional FK to existing universitas
            $table->unsignedBigInteger('id_universitas')->nullable();
            $table->foreign('id_universitas')
                  ->references('id_universitas')
                  ->on('universitas')
                  ->onDelete('set null');

            // Optional FK to existing perusahaan
            $table->unsignedBigInteger('id_perusahaan')->nullable();
            $table->foreign('id_perusahaan')
                  ->references('id_perusahaan')
                  ->on('perusahaan')
                  ->onDelete('set null');

            $table->timestamps();

            // Indexes for filtering
            $table->index('tipe');
            $table->index(['tipe', 'nama']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kemitraan');
    }
};
