<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_profile_updates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_alumni');
            $table->string('section'); // personal_info, skills, social_media, deskripsi_karier, portofolio
            $table->string('action')->default('update'); // create, update, delete
            $table->unsignedBigInteger('related_id')->nullable(); // e.g. id_deskripsi, id_portofolio for update/delete
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->string('foto_path')->nullable(); // temp foto path if uploaded
            $table->string('gambar_path')->nullable(); // temp gambar path for portofolio
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('id_alumni')->references('id_alumni')->on('alumni')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id_users')->on('users')->onDelete('set null');
            $table->index(['id_alumni', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_profile_updates');
    }
};
