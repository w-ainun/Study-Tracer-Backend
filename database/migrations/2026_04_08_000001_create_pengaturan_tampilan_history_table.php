<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create history table for pengaturan_tampilan snapshots.
     * Each row stores a full snapshot of settings before an update,
     * enabling the "Kembalikan Perubahan" (revert) feature.
     */
    public function up(): void
    {
        if (Schema::hasTable('pengaturan_tampilan_history')) {
            return;
        }

        Schema::create('pengaturan_tampilan_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pengaturan_tampilan_id');
            $table->json('snapshot');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->string('change_type', 50)->default('update'); // update, revert, reset
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('pengaturan_tampilan_id')
                ->references('id')
                ->on('pengaturan_tampilan')
                ->onDelete('cascade');

            $table->foreign('changed_by')
                ->references('id_users')
                ->on('users')
                ->onDelete('set null');

            // Index for quick latest-history lookup
            $table->index(['pengaturan_tampilan_id', 'created_at'], 'idx_history_pengaturan_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pengaturan_tampilan_history')) {
            Schema::dropIfExists('pengaturan_tampilan_history');
        }
    }
};
