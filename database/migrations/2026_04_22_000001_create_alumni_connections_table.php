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
        Schema::create('alumni_connections', function (Blueprint $table) {
            $table->id('id_connection');
            $table->unsignedBigInteger('id_alumni_requester');  // Yang mengirim permintaan
            $table->unsignedBigInteger('id_alumni_addressee');  // Yang menerima permintaan
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->foreign('id_alumni_requester')
                ->references('id_alumni')->on('alumni')
                ->onDelete('cascade');

            $table->foreign('id_alumni_addressee')
                ->references('id_alumni')->on('alumni')
                ->onDelete('cascade');

            // Satu alumni hanya bisa kirim 1 request ke alumni lain
            $table->unique(['id_alumni_requester', 'id_alumni_addressee'], 'unique_connection_request');

            // Performance indexes
            $table->index(['id_alumni_requester', 'status'], 'idx_requester_status');
            $table->index(['id_alumni_addressee', 'status'], 'idx_addressee_status');
            $table->index('status', 'idx_connection_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alumni_connections');
    }
};
