<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create tables for the real-time messaging system.
     *
     * Schema:
     * - conversations       : Each row is a chat room (1-on-1 or group).
     * - conversation_participants : Which users belong to which conversation.
     * - messages             : Individual messages inside a conversation.
     */
    public function up(): void
    {
        // =====================
        // CONVERSATIONS
        // =====================
        Schema::create('conversations', function (Blueprint $table) {
            $table->id('id_conversation');
            $table->enum('type', ['private', 'group'])->default('private');
            $table->string('group_name')->nullable();        // For group chats
            $table->string('group_avatar')->nullable();      // Group avatar URL
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')
                  ->references('id_users')->on('users')
                  ->nullOnDelete();

            $table->index('type');
            $table->index('created_at');
        });

        // =====================
        // CONVERSATION PARTICIPANTS
        // =====================
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id('id_participant');
            $table->unsignedBigInteger('id_conversation');
            $table->unsignedBigInteger('id_users');
            $table->enum('role', ['member', 'admin'])->default('member');
            $table->boolean('is_pinned')->default(false);     // Disematkan
            $table->boolean('is_archived')->default(false);   // Diarsipkan
            $table->boolean('is_muted')->default(false);      // Bisukan notifikasi
            $table->timestamp('last_read_at')->nullable();    // For read receipts
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable();         // Null = still in conversation
            $table->timestamps();

            $table->foreign('id_conversation')
                  ->references('id_conversation')->on('conversations')
                  ->cascadeOnDelete();

            $table->foreign('id_users')
                  ->references('id_users')->on('users')
                  ->cascadeOnDelete();

            $table->unique(['id_conversation', 'id_users']);
            $table->index(['id_users', 'left_at']);
            $table->index(['id_users', 'is_archived']);
            $table->index(['id_users', 'is_pinned']);
        });

        // =====================
        // MESSAGES
        // =====================
        Schema::create('messages', function (Blueprint $table) {
            $table->id('id_message');
            $table->unsignedBigInteger('id_conversation');
            $table->unsignedBigInteger('id_sender');         // id_users of the sender
            $table->enum('type', ['text', 'image', 'file', 'gif', 'system'])->default('text');
            $table->text('body')->nullable();                 // Text content or caption
            $table->string('file_url')->nullable();           // URL for image/file/gif
            $table->string('file_name')->nullable();          // Original filename
            $table->string('file_mime')->nullable();          // MIME type
            $table->unsignedInteger('file_size')->nullable(); // File size in bytes
            $table->unsignedBigInteger('reply_to_id')->nullable(); // Reply-to message
            $table->boolean('is_deleted')->default(false);    // Soft-delete for individual msgs
            $table->timestamps();

            $table->foreign('id_conversation')
                  ->references('id_conversation')->on('conversations')
                  ->cascadeOnDelete();

            $table->foreign('id_sender')
                  ->references('id_users')->on('users')
                  ->cascadeOnDelete();

            $table->foreign('reply_to_id')
                  ->references('id_message')->on('messages')
                  ->nullOnDelete();

            $table->index(['id_conversation', 'created_at']);
            $table->index(['id_sender', 'created_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
    }
};
