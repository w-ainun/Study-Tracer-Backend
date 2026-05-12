<?php

namespace App\Services\Alumni;

use App\Interfaces\Alumni\PostRepositoryInterface;
use App\Models\Alumni;
use App\Models\Post;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PostService
{
    private PostRepositoryInterface $postRepository;
    private NotificationService $notificationService;

    public function __construct(
        PostRepositoryInterface $postRepository,
        NotificationService $notificationService
    ) {
        $this->postRepository = $postRepository;
        $this->notificationService = $notificationService;
    }

    // =====================
    // FEED
    // =====================

    /**
     * Get feed postingan (semua postingan aktif, untuk semua alumni).
     */
    public function getFeed(int $userId, int $perPage = 10)
    {
        $alumni = $this->getAlumniByUserId($userId);

        return $this->postRepository->getFeed($alumni->id_alumni, $perPage);
    }

    /**
     * Get postingan milik alumni tertentu.
     */
    public function getPostsByAlumni(int $userId, ?int $targetAlumniId = null, int $perPage = 10)
    {
        $alumni = $this->getAlumniByUserId($userId);
        $alumniId = $targetAlumniId ?? $alumni->id_alumni;

        return $this->postRepository->getPostsByAlumni($alumniId, $perPage);
    }

    /**
     * Get single post.
     */
    public function getPost(int $userId, int $postId)
    {
        $alumni = $this->getAlumniByUserId($userId);
        $post = $this->postRepository->findPost($postId);

        if (!$post || !$post->is_active) {
            throw new \Exception('Postingan tidak ditemukan.');
        }

        // Tambah is_liked untuk alumni saat ini
        $post->is_liked = $this->postRepository->hasLiked($postId, $alumni->id_alumni);

        return $post;
    }

    // =====================
    // CRUD POSTS
    // =====================

    /**
     * Buat postingan baru (dengan optional images).
     */
    public function createPost(int $userId, array $data, array $images = [])
    {
        $alumni = $this->getAlumniByUserId($userId);

        return DB::transaction(function () use ($alumni, $data, $images) {
            $post = $this->postRepository->createPost([
                'id_alumni'  => $alumni->id_alumni,
                'content'    => $data['content'],
                'visibility' => 'public',
            ]);

            // Upload & simpan gambar
            if (!empty($images)) {
                $imagePaths = $this->uploadImages($images);
                $this->postRepository->addImages($post->id_post, $imagePaths);
            }

            return $this->postRepository->findPost($post->id_post);
        });
    }

    /**
     * Update postingan (hanya pemilik).
     */
    public function updatePost(int $userId, int $postId, array $data, array $newImages = [], array $removeImageIds = [])
    {
        $alumni = $this->getAlumniByUserId($userId);
        $post = $this->postRepository->findPost($postId);

        if (!$post || !$post->is_active) {
            throw new \Exception('Postingan tidak ditemukan.');
        }

        if ($post->id_alumni !== $alumni->id_alumni) {
            throw new \Exception('Anda tidak berhak mengedit postingan ini.');
        }

        return DB::transaction(function () use ($post, $data, $newImages, $removeImageIds) {
            // Update konten & visibility
            $updateData = [];
            if (isset($data['content'])) {
                $updateData['content'] = $data['content'];
            }
            if (isset($data['visibility'])) {
                $updateData['visibility'] = $data['visibility'];
            }

            if (!empty($updateData)) {
                $this->postRepository->updatePost($post->id_post, $updateData);
            }

            // Hapus gambar yang di-remove
            foreach ($removeImageIds as $imageId) {
                $image = $post->images->firstWhere('id_post_image', $imageId);
                if ($image) {
                    Storage::disk('public')->delete($image->image_path);
                    $this->postRepository->removeImage($imageId);
                }
            }

            // Upload & tambah gambar baru
            if (!empty($newImages)) {
                $imagePaths = $this->uploadImages($newImages);
                $this->postRepository->addImages($post->id_post, $imagePaths);
            }

            return $this->postRepository->findPost($post->id_post);
        });
    }

    /**
     * Hapus postingan (soft delete, hanya pemilik).
     */
    public function deletePost(int $userId, int $postId)
    {
        $alumni = $this->getAlumniByUserId($userId);
        $post = $this->postRepository->findPost($postId);

        if (!$post || !$post->is_active) {
            throw new \Exception('Postingan tidak ditemukan.');
        }

        if ($post->id_alumni !== $alumni->id_alumni) {
            throw new \Exception('Anda tidak berhak menghapus postingan ini.');
        }

        return $this->postRepository->deletePost($postId);
    }

    // =====================
    // LIKES
    // =====================

    /**
     * Toggle like/unlike pada postingan.
     */
    public function toggleLike(int $userId, int $postId)
    {
        $alumni = $this->getAlumniByUserId($userId);
        $post = $this->postRepository->findPost($postId);

        if (!$post || !$post->is_active) {
            throw new \Exception('Postingan tidak ditemukan.');
        }

        $result = $this->postRepository->toggleLike($postId, $alumni->id_alumni);

        // Kirim notifikasi jika like (bukan unlike) dan bukan post sendiri
        if ($result['liked'] && $post->id_alumni !== $alumni->id_alumni) {
            $postOwner = $post->alumni;
            if ($postOwner && $postOwner->user) {
                $this->notificationService->create(
                    $postOwner->user->id_users,
                    'post',
                    'Suka pada Postingan',
                    "{$alumni->nama_alumni} menyukai postingan Anda.",
                    [
                        'post_id'   => $postId,
                        'alumni_id' => $alumni->id_alumni,
                        'alumni_name' => $alumni->nama_alumni,
                    ]
                );
            }
        }

        return $result;
    }

    /**
     * Get daftar alumni yang like postingan.
     */
    public function getLikers(int $postId, int $perPage = 20)
    {
        $post = $this->postRepository->findPost($postId);

        if (!$post || !$post->is_active) {
            throw new \Exception('Postingan tidak ditemukan.');
        }

        return $this->postRepository->getLikers($postId, $perPage);
    }

    // =====================
    // COMMENTS
    // =====================

    /**
     * Get komentar pada postingan.
     */
    public function getComments(int $postId, int $perPage = 15)
    {
        $post = $this->postRepository->findPost($postId);

        if (!$post || !$post->is_active) {
            throw new \Exception('Postingan tidak ditemukan.');
        }

        return $this->postRepository->getComments($postId, $perPage);
    }

    /**
     * Get replies pada komentar.
     */
    public function getReplies(int $commentId, int $perPage = 10)
    {
        $comment = $this->postRepository->findComment($commentId);

        if (!$comment || !$comment->is_active) {
            throw new \Exception('Komentar tidak ditemukan.');
        }

        return $this->postRepository->getReplies($commentId, $perPage);
    }

    /**
     * Tambah komentar pada postingan.
     */
    public function addComment(int $userId, int $postId, string $content, ?int $parentCommentId = null)
    {
        $alumni = $this->getAlumniByUserId($userId);
        $post = $this->postRepository->findPost($postId);

        if (!$post || !$post->is_active) {
            throw new \Exception('Postingan tidak ditemukan.');
        }

        // Jika reply, validasi parent comment ada dan aktif
        if ($parentCommentId) {
            $parentComment = $this->postRepository->findComment($parentCommentId);
            if (!$parentComment || !$parentComment->is_active) {
                throw new \Exception('Komentar yang ingin di-reply tidak ditemukan.');
            }
            if ($parentComment->id_post !== $postId) {
                throw new \Exception('Komentar tidak terkait dengan postingan ini.');
            }
        }

        $comment = $this->postRepository->createComment([
            'id_post'           => $postId,
            'id_alumni'         => $alumni->id_alumni,
            'id_parent_comment' => $parentCommentId,
            'content'           => $content,
        ]);

        // Kirim notifikasi ke pemilik post (jika bukan diri sendiri)
        if ($post->id_alumni !== $alumni->id_alumni) {
            $postOwner = $post->alumni;
            if ($postOwner && $postOwner->user) {
                $action = $parentCommentId ? 'membalas komentar di' : 'mengomentari';
                $this->notificationService->create(
                    $postOwner->user->id_users,
                    'post',
                    'Komentar Baru',
                    "{$alumni->nama_alumni} {$action} postingan Anda.",
                    [
                        'post_id'    => $postId,
                        'comment_id' => $comment->id_comment,
                        'alumni_id'  => $alumni->id_alumni,
                        'alumni_name' => $alumni->nama_alumni,
                    ]
                );
            }
        }

        // Jika ini reply, notifikasi juga ke pemilik parent comment (jika berbeda dari post owner dan diri sendiri)
        if ($parentCommentId) {
            $parentComment = $this->postRepository->findComment($parentCommentId);
            if ($parentComment
                && $parentComment->id_alumni !== $alumni->id_alumni
                && $parentComment->id_alumni !== $post->id_alumni
            ) {
                $parentOwner = $parentComment->alumni;
                if ($parentOwner && $parentOwner->user) {
                    $this->notificationService->create(
                        $parentOwner->user->id_users,
                        'post',
                        'Balasan Komentar',
                        "{$alumni->nama_alumni} membalas komentar Anda.",
                        [
                            'post_id'    => $postId,
                            'comment_id' => $comment->id_comment,
                            'alumni_id'  => $alumni->id_alumni,
                            'alumni_name' => $alumni->nama_alumni,
                        ]
                    );
                }
            }
        }

        return $comment;
    }

    /**
     * Update komentar (hanya pemilik).
     */
    public function updateComment(int $userId, int $commentId, string $content)
    {
        $alumni = $this->getAlumniByUserId($userId);
        $comment = $this->postRepository->findComment($commentId);

        if (!$comment || !$comment->is_active) {
            throw new \Exception('Komentar tidak ditemukan.');
        }

        if ($comment->id_alumni !== $alumni->id_alumni) {
            throw new \Exception('Anda tidak berhak mengedit komentar ini.');
        }

        return $this->postRepository->updateComment($commentId, $content);
    }

    /**
     * Hapus komentar (pemilik komentar ATAU pemilik postingan).
     */
    public function deleteComment(int $userId, int $commentId)
    {
        $alumni = $this->getAlumniByUserId($userId);
        $comment = $this->postRepository->findComment($commentId);

        if (!$comment || !$comment->is_active) {
            throw new \Exception('Komentar tidak ditemukan.');
        }

        $post = $this->postRepository->findPost($comment->id_post);

        // Boleh dihapus oleh pemilik komentar atau pemilik postingan
        if ($comment->id_alumni !== $alumni->id_alumni && $post->id_alumni !== $alumni->id_alumni) {
            throw new \Exception('Anda tidak berhak menghapus komentar ini.');
        }

        return $this->postRepository->deleteComment($commentId);
    }

    // =====================
    // REPORTS
    // =====================

    /**
     * Report postingan yang tidak pantas.
     */
    public function reportPost(int $userId, int $postId, string $reason, ?string $description = null)
    {
        $alumni = $this->getAlumniByUserId($userId);
        $post = $this->postRepository->findPost($postId);

        if (!$post || !$post->is_active) {
            throw new \Exception('Postingan tidak ditemukan.');
        }

        if ($post->id_alumni === $alumni->id_alumni) {
            throw new \Exception('Tidak dapat melaporkan postingan sendiri.');
        }

        if ($this->postRepository->hasReported($postId, $alumni->id_alumni)) {
            throw new \Exception('Anda sudah melaporkan postingan ini sebelumnya.');
        }

        return $this->postRepository->reportPost([
            'id_post'     => $postId,
            'id_alumni'   => $alumni->id_alumni,
            'reason'      => $reason,
            'description' => $description,
        ]);
    }

    // =====================
    // PRIVATE HELPERS
    // =====================

    /**
     * Get alumni dari user ID.
     */
    private function getAlumniByUserId(int $userId): Alumni
    {
        $alumni = Alumni::where('id_users', $userId)->first();

        if (!$alumni) {
            throw new \Exception('Profil alumni tidak ditemukan.');
        }

        return $alumni;
    }



    /**
     * Upload multiple images ke storage.
     */
    private function uploadImages(array $images): array
    {
        $paths = [];
        foreach ($images as $image) {
            $paths[] = $image->store('posts', 'public');
        }
        return $paths;
    }
}
