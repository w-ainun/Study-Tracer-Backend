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
        Schema::create('alumni_blocks', function (Blueprint $table) {
            $table->id('id_block');
            $table->unsignedBigInteger('id_alumni_blocker');  // Yang melakukan block
            $table->unsignedBigInteger('id_alumni_blocked');  // Yang di-block
            $table->timestamps();

            $table->foreign('id_alumni_blocker')
                ->references('id_alumni')->on('alumni')
                ->onDelete('cascade');

            $table->foreign('id_alumni_blocked')
                ->references('id_alumni')->on('alumni')
                ->onDelete('cascade');

            // Satu alumni hanya bisa block alumni lain 1 kali
            $table->unique(['id_alumni_blocker', 'id_alumni_blocked'], 'unique_block');

            // Performance indexes
            $table->index('id_alumni_blocker', 'idx_blocker');
            $table->index('id_alumni_blocked', 'idx_blocked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alumni_blocks');
    }
};
