<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin', function (Blueprint $table) {
            $table->id('id_admin');
            $table->string('nama_admin');
            $table->unsignedBigInteger('id_users')->unique();
            $table->foreign('id_users')->references('id_users')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin');
    }
};
