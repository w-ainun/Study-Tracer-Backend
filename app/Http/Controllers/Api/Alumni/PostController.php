<?php

namespace App\Http\Controllers\Api\Alumni;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportPostRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\Alumni\PostCommentResource;
use App\Http\Resources\Alumni\PostLikerResource;
use App\Http\Resources\Alumni\PostResource;
use App\Services\Alumni\PostService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    use ApiResponse;

    private PostService $postService;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    // =====================
    // FEED & POST ENDPOINTS
    // =====================

    /**
     * GET /alumni/posts/feed
     * Get feed postingan.
     *
     * Query params:
     *   - filter: 'connections' | 'all' (default: tanpa filter, semua post terbaru)
     *     - connections: Hanya postingan dari koneksi (+ milik sendiri), urut terbaru.
     *     - all: Semua postingan dari semua user, diurutkan berdasarkan engagement
     *            (likes + komentar terbanyak muncul di atas beranda).
     *   - per_page: jumlah post per halaman (default: 10)
     */
    public function feed(Request $request)
    {
        try {
            $userId = auth()->user()->id_users;
            $perPage = $request->input('per_page', 10);
            $filter = $request->input('filter');

            $paginated = match ($filter) {
                'connections' => $this->postService->getConnectionFeed($userId, $perPage),
                'all'         => $this->postService->getTrendingFeed($userId, $perPage),
                default       => $this->postService->getFeed($userId, $perPage),
            };

            return $this->successResponse(
                PostResource::collection($paginated)->response()->getData(true)
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil feed: ' . $e->getMessage());
        }
    }

    /**
     * GET /alumni/posts/my
     * Get postingan saya sendiri.
     */
    public function myPosts(Request $request)
    {
        try {
            $userId = auth()->user()->id_users;
            $perPage = $request->input('per_page', 10);
            $paginated = $this->postService->getPostsByAlumni($userId, null, $perPage);

            return $this->successResponse(
                PostResource::collection($paginated)->response()->getData(true)
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil postingan: ' . $e->getMessage());
        }
    }

    /**
     * GET /alumni/posts/alumni/{id}
     * Get postingan alumni tertentu.
     */
    public function alumniPosts(Request $request, int $id)
    {
        try {
            $userId = auth()->user()->id_users;
            $perPage = $request->input('per_page', 10);
            $paginated = $this->postService->getPostsByAlumni($userId, $id, $perPage);

            return $this->successResponse(
                PostResource::collection($paginated)->response()->getData(true)
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil postingan: ' . $e->getMessage());
        }
    }

    /**
     * GET /alumni/posts/{id}
     * Get detail single post.
     */
    public function show(int $id)
    {
        try {
            $userId = auth()->user()->id_users;
            $post = $this->postService->getPost($userId, $id);

            return $this->successResponse(new PostResource($post));
        } catch (\Exception $e) {
            return $this->notFoundResponse($e->getMessage());
        }
    }

    /**
     * POST /alumni/posts
     * Buat postingan baru.
     */
    public function store(StorePostRequest $request)
    {
        try {
            $userId = auth()->user()->id_users;
            $data = $request->only(['content', 'visibility']);
            $images = $request->file('images', []);

            $post = $this->postService->createPost($userId, $data, $images);

            return $this->createdResponse(
                new PostResource($post),
                'Postingan berhasil dibuat.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal membuat postingan: ' . $e->getMessage());
        }
    }

    /**
     * PUT/POST /alumni/posts/{id}
     * Update postingan.
     */
    public function update(UpdatePostRequest $request, int $id)
    {
        try {
            $userId = auth()->user()->id_users;
            $data = $request->only(['content', 'visibility']);
            $newImages = $request->file('images', []);
            $removeImageIds = $request->input('remove_images', []);

            $post = $this->postService->updatePost($userId, $id, $data, $newImages, $removeImageIds);

            return $this->successResponse(
                new PostResource($post),
                'Postingan berhasil diperbarui.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * DELETE /alumni/posts/{id}
     * Hapus postingan.
     */
    public function destroy(int $id)
    {
        try {
            $userId = auth()->user()->id_users;
            $this->postService->deletePost($userId, $id);

            return $this->successResponse(null, 'Postingan berhasil dihapus.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    // =====================
    // LIKE ENDPOINTS
    // =====================

    /**
     * POST /alumni/posts/{id}/like
     * Toggle like/unlike.
     */
    public function toggleLike(int $id)
    {
        try {
            $userId = auth()->user()->id_users;
            $result = $this->postService->toggleLike($userId, $id);

            $message = $result['liked'] ? 'Postingan disukai.' : 'Like dibatalkan.';
            return $this->successResponse($result, $message);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * GET /alumni/posts/{id}/likers
     * Get daftar alumni yang like.
     */
    public function likers(Request $request, int $id)
    {
        try {
            $perPage = $request->input('per_page', 20);
            $paginated = $this->postService->getLikers($id, $perPage);

            return $this->successResponse([
                'data'         => PostLikerResource::collection($paginated),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    // =====================
    // COMMENT ENDPOINTS
    // =====================

    /**
     * GET /alumni/posts/{id}/comments
     * Get komentar pada postingan.
     */
    public function comments(Request $request, int $id)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $paginated = $this->postService->getComments($id, $perPage);

            return $this->successResponse(
                PostCommentResource::collection($paginated)->response()->getData(true)
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * POST /alumni/posts/{id}/comments
     * Tambah komentar.
     */
    public function addComment(StoreCommentRequest $request, int $id)
    {
        try {
            $userId = auth()->user()->id_users;
            $content = $request->input('content');
            $parentCommentId = $request->input('id_parent_comment');

            $comment = $this->postService->addComment($userId, $id, $content, $parentCommentId);

            return $this->createdResponse(
                new PostCommentResource($comment),
                'Komentar berhasil ditambahkan.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * GET /alumni/posts/comments/{id}/replies
     * Get replies pada komentar.
     */
    public function replies(Request $request, int $id)
    {
        try {
            $perPage = $request->input('per_page', 10);
            $paginated = $this->postService->getReplies($id, $perPage);

            return $this->successResponse(
                PostCommentResource::collection($paginated)->response()->getData(true)
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * PUT /alumni/posts/comments/{id}
     * Update komentar.
     */
    public function updateComment(Request $request, int $id)
    {
        try {
            $request->validate([
                'content' => 'required|string|max:2000',
            ]);

            $userId = auth()->user()->id_users;
            $comment = $this->postService->updateComment($userId, $id, $request->input('content'));

            return $this->successResponse(
                new PostCommentResource($comment),
                'Komentar berhasil diperbarui.'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * DELETE /alumni/posts/comments/{id}
     * Hapus komentar.
     */
    public function deleteComment(int $id)
    {
        try {
            $userId = auth()->user()->id_users;
            $this->postService->deleteComment($userId, $id);

            return $this->successResponse(null, 'Komentar berhasil dihapus.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    // =====================
    // REPORT ENDPOINTS
    // =====================

    /**
     * POST /alumni/posts/{id}/report
     * Laporkan postingan.
     */
    public function report(ReportPostRequest $request, int $id)
    {
        try {
            $userId = auth()->user()->id_users;
            $reason = $request->input('reason');
            $description = $request->input('description');

            $this->postService->reportPost($userId, $id, $reason, $description);

            return $this->successResponse(null, 'Postingan berhasil dilaporkan.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
