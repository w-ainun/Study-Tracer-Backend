<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mini Medsos tables:
     * - posts: postingan alumni
     * - post_images: multiple images per post
     * - post_likes: like/unlike toggle
     * - post_comments: komentar pada postingan
     * - post_reports: laporan postingan yang tidak pantas
     */
    public function up(): void
    {
        // =====================
        // POSTS (Postingan Alumni)
        // =====================
        Schema::create('posts', function (Blueprint $table) {
            $table->id('id_post');
            $table->unsignedBigInteger('id_alumni');
            $table->text('content');
            $table->enum('visibility', ['connections', 'public'])->default('connections');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('id_alumni')
                  ->references('id_alumni')->on('alumni')
                  ->onDelete('cascade');

            // Indexes for feed queries
            $table->index(['id_alumni', 'is_active', 'created_at']);
            $table->index(['is_active', 'created_at']);
            $table->index('visibility');
        });

        // =====================
        // POST IMAGES (Multi-image support)
        // =====================
        Schema::create('post_images', function (Blueprint $table) {
            $table->id('id_post_image');
            $table->unsignedBigInteger('id_post');
            $table->string('image_path');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('id_post')
                  ->references('id_post')->on('posts')
                  ->onDelete('cascade');

            $table->index('id_post');
        });

        // =====================
        // POST LIKES (Toggle like/unlike)
        // =====================
        Schema::create('post_likes', function (Blueprint $table) {
            $table->id('id_post_like');
            $table->unsignedBigInteger('id_post');
            $table->unsignedBigInteger('id_alumni');
            $table->timestamps();

            $table->foreign('id_post')
                  ->references('id_post')->on('posts')
                  ->onDelete('cascade');
            $table->foreign('id_alumni')
                  ->references('id_alumni')->on('alumni')
                  ->onDelete('cascade');

            // Unique constraint: satu alumni hanya bisa like sekali per post
            $table->unique(['id_post', 'id_alumni']);
            $table->index('id_alumni');
        });

        // =====================
        // POST COMMENTS (Komentar)
        // =====================
        Schema::create('post_comments', function (Blueprint $table) {
            $table->id('id_comment');
            $table->unsignedBigInteger('id_post');
            $table->unsignedBigInteger('id_alumni');
            $table->unsignedBigInteger('id_parent_comment')->nullable(); // Reply to comment
            $table->text('content');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('id_post')
                  ->references('id_post')->on('posts')
                  ->onDelete('cascade');
            $table->foreign('id_alumni')
                  ->references('id_alumni')->on('alumni')
                  ->onDelete('cascade');
            $table->foreign('id_parent_comment')
                  ->references('id_comment')->on('post_comments')
                  ->onDelete('cascade');

            $table->index(['id_post', 'is_active', 'created_at']);
            $table->index('id_alumni');
            $table->index('id_parent_comment');
        });

        // =====================
        // POST REPORTS (Laporan postingan)
        // =====================
        Schema::create('post_reports', function (Blueprint $table) {
            $table->id('id_report');
            $table->unsignedBigInteger('id_post');
            $table->unsignedBigInteger('id_alumni');
            $table->string('reason');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'reviewed', 'dismissed'])->default('pending');
            $table->timestamps();

            $table->foreign('id_post')
                  ->references('id_post')->on('posts')
                  ->onDelete('cascade');
            $table->foreign('id_alumni')
                  ->references('id_alumni')->on('alumni')
                  ->onDelete('cascade');

            // Satu alumni hanya bisa report satu post sekali
            $table->unique(['id_post', 'id_alumni']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_reports');
        Schema::dropIfExists('post_comments');
        Schema::dropIfExists('post_likes');
        Schema::dropIfExists('post_images');
        Schema::dropIfExists('posts');
    }
};
