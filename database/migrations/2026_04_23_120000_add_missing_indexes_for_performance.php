<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add targeted indexes for high-traffic query paths that are still missing.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->index(['id_conversation', 'is_deleted', 'id_message'], 'idx_messages_conv_deleted_message');
        });

        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->index(['id_users', 'left_at', 'is_archived'], 'idx_participants_user_left_archived');
        });

        Schema::table('alumni_blocks', function (Blueprint $table) {
            $table->index(['id_alumni_blocker', 'created_at'], 'idx_blocks_blocker_created');
        });

        Schema::table('kemitraan', function (Blueprint $table) {
            $table->index(['tipe', 'id_kemitraan'], 'idx_kemitraan_tipe_id');
            $table->index('updated_at', 'idx_kemitraan_updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('kemitraan', function (Blueprint $table) {
            $table->dropIndex('idx_kemitraan_tipe_id');
            $table->dropIndex('idx_kemitraan_updated_at');
        });

        Schema::table('alumni_blocks', function (Blueprint $table) {
            $table->dropIndex('idx_blocks_blocker_created');
        });

        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->dropIndex('idx_participants_user_left_archived');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('idx_messages_conv_deleted_message');
        });
    }
};
