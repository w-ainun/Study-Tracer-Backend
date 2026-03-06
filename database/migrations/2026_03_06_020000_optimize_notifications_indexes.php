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
        Schema::table('notifications', function (Blueprint $table) {
            // Composite index untuk query pagination + filter unread
            // Index ini akan mempercepat query: WHERE id_users = ? ORDER BY created_at DESC
            $table->index(['id_users', 'created_at'], 'idx_notifications_user_date');
            
            // Composite index untuk unread count query yang lebih optimal
            // Index ini akan mempercepat query: WHERE id_users = ? AND is_read = false
            $table->index(['id_users', 'is_read', 'created_at'], 'idx_notifications_user_read_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_user_date');
            $table->dropIndex('idx_notifications_user_read_date');
        });
    }
};
