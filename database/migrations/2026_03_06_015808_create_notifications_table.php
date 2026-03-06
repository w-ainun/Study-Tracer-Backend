<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id('id_notification');
            $table->foreignId('id_users')->constrained('users', 'id_users')->onDelete('cascade');
            $table->enum('type', ['verification', 'lowongan', 'career_status', 'kuesioner', 'system'])->default('system');
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // untuk menyimpan data tambahan seperti id lowongan, id status karir, dll
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            
            $table->index(['id_users', 'is_read']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
