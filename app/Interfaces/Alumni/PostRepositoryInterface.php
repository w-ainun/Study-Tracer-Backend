<?php

namespace App\Interfaces\Alumni;

use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostLike;
use App\Models\PostReport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface PostRepositoryInterface
{
    // =====================
    // POSTS
    // =====================

    /**
     * Get feed postingan untuk semua alumni.
     */
    public function getFeed(int $alumniId, int $perPage = 10): LengthAwarePaginator;

    /**
     * Get postingan milik alumni tertentu.
     */
    public function getPostsByAlumni(int $alumniId, int $perPage = 10): LengthAwarePaginator;

    /**
     * Get single post by ID.
     */
    public function findPost(int $postId): ?Post;

    /**
     * Create a new post.
     */
    public function createPost(array $data): Post;

    /**
     * Update an existing post.
     */
    public function updatePost(int $postId, array $data): Post;

    /**
     * Soft-delete a post (set is_active = false).
     */
    public function deletePost(int $postId): bool;

    // =====================
    // IMAGES
    // =====================

    /**
     * Tambah gambar ke post.
     */
    public function addImages(int $postId, array $imagePaths): void;

    /**
     * Hapus gambar dari post.
     */
    public function removeImage(int $imageId): bool;

    // =====================
    // LIKES
    // =====================

    /**
     * Toggle like (like jika belum, unlike jika sudah).
     */
    public function toggleLike(int $postId, int $alumniId): array;

    /**
     * Get alumni yang like post.
     */
    public function getLikers(int $postId, int $perPage = 20): LengthAwarePaginator;

    /**
     * Cek apakah alumni sudah like post.
     */
    public function hasLiked(int $postId, int $alumniId): bool;

    // =====================
    // COMMENTS
    // =====================

    /**
     * Get komentar pada post (paginated).
     */
    public function getComments(int $postId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get replies pada komentar.
     */
    public function getReplies(int $commentId, int $perPage = 10): LengthAwarePaginator;

    /**
     * Create a comment.
     */
    public function createComment(array $data): PostComment;

    /**
     * Update a comment.
     */
    public function updateComment(int $commentId, string $content): PostComment;

    /**
     * Soft-delete a comment (set is_active = false).
     */
    public function deleteComment(int $commentId): bool;

    /**
     * Find comment by ID.
     */
    public function findComment(int $commentId): ?PostComment;

    // =====================
    // REPORTS
    // =====================

    /**
     * Report a post.
     */
    public function reportPost(array $data): PostReport;

    /**
     * Cek apakah alumni sudah report post.
     */
    public function hasReported(int $postId, int $alumniId): bool;
}
