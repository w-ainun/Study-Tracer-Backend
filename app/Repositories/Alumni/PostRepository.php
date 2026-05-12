<?php

namespace App\Repositories\Alumni;

use App\Interfaces\Alumni\PostRepositoryInterface;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostImage;
use App\Models\PostLike;
use App\Models\PostReport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PostRepository implements PostRepositoryInterface
{
    // =====================
    // POSTS
    // =====================

    /**
     * Get feed postingan untuk semua alumni.
     * Semua postingan aktif bisa dilihat oleh siapa saja.
     * Urutan: terbaru terlebih dahulu.
     */
    public function getFeed(int $alumniId, int $perPage = 10): LengthAwarePaginator
    {
        return Post::active()
            ->with([
                'alumni:id_alumni,nama_alumni,foto,id_jurusan',
                'alumni.jurusan:id_jurusan,nama_jurusan',
                'images',
            ])
            ->withCount(['likes', 'allComments as comments_count'])
            ->withExists(['likes as is_liked' => function ($q) use ($alumniId) {
                $q->where('id_alumni', $alumniId);
            }])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Get postingan milik alumni tertentu.
     */
    public function getPostsByAlumni(int $alumniId, int $perPage = 10): LengthAwarePaginator
    {
        return Post::active()
            ->byAlumni($alumniId)
            ->with([
                'alumni:id_alumni,nama_alumni,foto,id_jurusan',
                'alumni.jurusan:id_jurusan,nama_jurusan',
                'images',
            ])
            ->withCount(['likes', 'allComments as comments_count'])
            ->withExists(['likes as is_liked' => function ($q) use ($alumniId) {
                $q->where('id_alumni', $alumniId);
            }])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Get single post by ID.
     */
    public function findPost(int $postId): ?Post
    {
        return Post::with([
            'alumni:id_alumni,nama_alumni,foto,id_jurusan',
            'alumni.jurusan:id_jurusan,nama_jurusan',
            'images',
        ])
        ->withCount(['likes', 'allComments as comments_count'])
        ->find($postId);
    }

    /**
     * Create a new post.
     */
    public function createPost(array $data): Post
    {
        $post = Post::create($data);
        return $post->fresh([
            'alumni:id_alumni,nama_alumni,foto,id_jurusan',
            'alumni.jurusan:id_jurusan,nama_jurusan',
            'images',
        ]);
    }

    /**
     * Update an existing post.
     */
    public function updatePost(int $postId, array $data): Post
    {
        $post = Post::findOrFail($postId);
        $post->update($data);
        return $post->fresh([
            'alumni:id_alumni,nama_alumni,foto,id_jurusan',
            'alumni.jurusan:id_jurusan,nama_jurusan',
            'images',
        ]);
    }

    /**
     * Soft-delete a post (set is_active = false).
     */
    public function deletePost(int $postId): bool
    {
        return Post::where('id_post', $postId)
            ->update(['is_active' => false]) > 0;
    }

    // =====================
    // IMAGES
    // =====================

    /**
     * Tambah gambar ke post.
     */
    public function addImages(int $postId, array $imagePaths): void
    {
        $lastOrder = PostImage::where('id_post', $postId)->max('sort_order') ?? -1;

        foreach ($imagePaths as $index => $path) {
            PostImage::create([
                'id_post'    => $postId,
                'image_path' => $path,
                'sort_order' => $lastOrder + $index + 1,
            ]);
        }
    }

    /**
     * Hapus gambar dari post.
     */
    public function removeImage(int $imageId): bool
    {
        return PostImage::where('id_post_image', $imageId)->delete() > 0;
    }

    // =====================
    // LIKES
    // =====================

    /**
     * Toggle like (like jika belum, unlike jika sudah).
     * Returns ['liked' => bool, 'likes_count' => int]
     */
    public function toggleLike(int $postId, int $alumniId): array
    {
        $existing = PostLike::where('id_post', $postId)
            ->where('id_alumni', $alumniId)
            ->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            PostLike::create([
                'id_post'   => $postId,
                'id_alumni' => $alumniId,
            ]);
            $liked = true;
        }

        $count = PostLike::where('id_post', $postId)->count();

        return [
            'liked'       => $liked,
            'likes_count' => $count,
        ];
    }

    /**
     * Get alumni yang like post.
     */
    public function getLikers(int $postId, int $perPage = 20): LengthAwarePaginator
    {
        return PostLike::where('id_post', $postId)
            ->with(['alumni:id_alumni,nama_alumni,foto,id_jurusan', 'alumni.jurusan:id_jurusan,nama_jurusan'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Cek apakah alumni sudah like post.
     */
    public function hasLiked(int $postId, int $alumniId): bool
    {
        return PostLike::where('id_post', $postId)
            ->where('id_alumni', $alumniId)
            ->exists();
    }

    // =====================
    // COMMENTS
    // =====================

    /**
     * Get komentar pada post (paginated, top-level with replies count).
     */
    public function getComments(int $postId, int $perPage = 15): LengthAwarePaginator
    {
        return PostComment::where('id_post', $postId)
            ->active()
            ->topLevel()
            ->with([
                'alumni:id_alumni,nama_alumni,foto,id_jurusan',
                'alumni.jurusan:id_jurusan,nama_jurusan',
            ])
            ->withCount(['replies' => function ($q) {
                $q->where('is_active', true);
            }])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Get replies pada komentar.
     */
    public function getReplies(int $commentId, int $perPage = 10): LengthAwarePaginator
    {
        return PostComment::where('id_parent_comment', $commentId)
            ->active()
            ->with([
                'alumni:id_alumni,nama_alumni,foto,id_jurusan',
                'alumni.jurusan:id_jurusan,nama_jurusan',
            ])
            ->orderBy('created_at')
            ->paginate($perPage);
    }

    /**
     * Create a comment.
     */
    public function createComment(array $data): PostComment
    {
        $comment = PostComment::create($data);
        return $comment->fresh([
            'alumni:id_alumni,nama_alumni,foto,id_jurusan',
            'alumni.jurusan:id_jurusan,nama_jurusan',
        ]);
    }

    /**
     * Update a comment.
     */
    public function updateComment(int $commentId, string $content): PostComment
    {
        $comment = PostComment::findOrFail($commentId);
        $comment->update(['content' => $content]);
        return $comment->fresh([
            'alumni:id_alumni,nama_alumni,foto,id_jurusan',
            'alumni.jurusan:id_jurusan,nama_jurusan',
        ]);
    }

    /**
     * Soft-delete a comment.
     */
    public function deleteComment(int $commentId): bool
    {
        return PostComment::where('id_comment', $commentId)
            ->update(['is_active' => false]) > 0;
    }

    /**
     * Find comment by ID.
     */
    public function findComment(int $commentId): ?PostComment
    {
        return PostComment::with([
            'alumni:id_alumni,nama_alumni,foto,id_jurusan',
        ])->find($commentId);
    }

    // =====================
    // REPORTS
    // =====================

    /**
     * Report a post.
     */
    public function reportPost(array $data): PostReport
    {
        return PostReport::create($data);
    }

    /**
     * Cek apakah alumni sudah report post.
     */
    public function hasReported(int $postId, int $alumniId): bool
    {
        return PostReport::where('id_post', $postId)
            ->where('id_alumni', $alumniId)
            ->exists();
    }
}
