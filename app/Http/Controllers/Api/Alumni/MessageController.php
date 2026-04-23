<?php

namespace App\Http\Controllers\Api\Alumni;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateGroupConversationRequest;
use App\Http\Requests\SendMessageRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Services\MessageService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    use ApiResponse;

    public function __construct(
        private MessageService $messageService,
    ) {}

    // =====================
    // CONVERSATIONS
    // =====================

    /**
     * GET /alumni/messages/conversations
     * List all conversations for the authenticated user.
     */
    public function conversations(Request $request)
    {
        try {
            $userId  = auth()->user()->id_users;
            $search  = $request->input('search');
            $perPage = $request->input('per_page', 20);

            $paginated = $this->messageService->getConversations($userId, $search, $perPage);

            return $this->successResponse([
                'data'         => ConversationResource::collectionWithUser($paginated->getCollection(), $userId),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil daftar percakapan: ' . $e->getMessage());
        }
    }

    /**
     * GET /alumni/messages/conversations/{id}
     * Get a single conversation detail.
     */
    public function showConversation(int $id)
    {
        try {
            $userId = auth()->user()->id_users;
            $conversation = $this->messageService->getConversation($userId, $id);

            return $this->successResponse(new ConversationResource($conversation, $userId));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * POST /alumni/messages/conversations/private
     * Get or create a private (1-on-1) conversation with a target alumni.
     */
    public function getOrCreatePrivate(Request $request)
    {
        $request->validate([
            'id_alumni' => 'required|integer|exists:alumni,id_alumni',
        ]);

        try {
            $userId = auth()->user()->id_users;
            $conversation = $this->messageService->getOrCreatePrivateConversation(
                $userId,
                $request->id_alumni
            );

            return $this->successResponse(
                new ConversationResource($conversation, $userId),
                'Percakapan berhasil dibuat/ditemukan.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * POST /alumni/messages/conversations/group
     * Create a new group conversation.
     */
    public function createGroup(CreateGroupConversationRequest $request)
    {
        try {
            $userId = auth()->user()->id_users;

            $avatarPath = null;
            if ($request->hasFile('avatar')) {
                $avatarPath = Storage::url(
                    $request->file('avatar')->store('group-avatars', 'public')
                );
            }

            $conversation = $this->messageService->createGroupConversation(
                $userId,
                $request->group_name,
                $request->participants,
                $avatarPath
            );

            return $this->createdResponse(
                new ConversationResource($conversation, $userId),
                'Grup berhasil dibuat.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * PUT /alumni/messages/conversations/{id}/group
     * Update group name/avatar.
     */
    public function updateGroup(Request $request, int $id)
    {
        $request->validate([
            'group_name' => 'nullable|string|max:100',
            'avatar'     => 'nullable|image|max:2048',
        ]);

        try {
            $userId = auth()->user()->id_users;

            $data = [];
            if ($request->group_name) {
                $data['group_name'] = $request->group_name;
            }
            if ($request->hasFile('avatar')) {
                $data['group_avatar'] = Storage::url(
                    $request->file('avatar')->store('group-avatars', 'public')
                );
            }

            $conversation = $this->messageService->updateGroupConversation($userId, $id, $data);

            return $this->successResponse(
                new ConversationResource($conversation, $userId),
                'Grup berhasil diperbarui.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * POST /alumni/messages/conversations/{id}/leave
     * Leave a group conversation.
     */
    public function leaveConversation(int $id)
    {
        try {
            $userId = auth()->user()->id_users;
            $this->messageService->leaveConversation($userId, $id);

            return $this->successResponse(null, 'Berhasil keluar dari grup.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * DELETE /alumni/messages/conversations/{id}
     * Delete (hide/archive) a conversation for the current user.
     */
    public function deleteConversation(int $id)
    {
        try {
            $userId = auth()->user()->id_users;
            $this->messageService->deleteConversationForUser($userId, $id);

            return $this->successResponse(null, 'Percakapan berhasil dihapus.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    // =====================
    // CONVERSATION SETTINGS
    // =====================

    /**
     * POST /alumni/messages/conversations/{id}/pin
     * Toggle pin/unpin a conversation.
     */
    public function togglePin(int $id)
    {
        try {
            $userId = auth()->user()->id_users;
            $isPinned = $this->messageService->togglePin($userId, $id);

            return $this->successResponse(
                ['is_pinned' => $isPinned],
                $isPinned ? 'Percakapan disematkan.' : 'Sematan dihapus.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * POST /alumni/messages/conversations/{id}/mute
     * Toggle mute/unmute a conversation.
     */
    public function toggleMute(int $id)
    {
        try {
            $userId = auth()->user()->id_users;
            $isMuted = $this->messageService->toggleMute($userId, $id);

            return $this->successResponse(
                ['is_muted' => $isMuted],
                $isMuted ? 'Notifikasi dimatikan.' : 'Notifikasi diaktifkan.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    // =====================
    // MESSAGES
    // =====================

    /**
     * GET /alumni/messages/conversations/{id}/messages
     * Get paginated messages for a conversation.
     */
    public function messages(Request $request, int $id)
    {
        try {
            $userId  = auth()->user()->id_users;
            $perPage = $request->input('per_page', 30);

            $paginated = $this->messageService->getMessages($userId, $id, $perPage);

            return $this->successResponse([
                'data'         => MessageResource::collection($paginated),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * POST /alumni/messages/conversations/{id}/messages
     * Send a message (text, image, file, or GIF).
     */
    public function sendMessage(SendMessageRequest $request, int $id)
    {
        try {
            $userId = auth()->user()->id_users;

            $data = [
                'type'        => $request->type ?? 'text',
                'body'        => $request->body,
                'reply_to_id' => $request->reply_to_id,
                'gif_url'     => $request->gif_url,
            ];

            if ($request->hasFile('file')) {
                $data['file'] = $request->file('file');
            }

            $message = $this->messageService->sendMessage($userId, $id, $data);

            return $this->createdResponse(
                new MessageResource($message),
                'Pesan berhasil dikirim.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * DELETE /alumni/messages/{id}
     * Delete (soft) a message.
     */
    public function deleteMessage(int $id)
    {
        try {
            $userId = auth()->user()->id_users;
            $this->messageService->deleteMessage($userId, $id);

            return $this->successResponse(null, 'Pesan berhasil dihapus.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    // =====================
    // READ RECEIPTS & TYPING
    // =====================

    /**
     * POST /alumni/messages/conversations/{id}/read
     * Mark all messages in a conversation as read.
     */
    public function markAsRead(int $id)
    {
        try {
            $userId = auth()->user()->id_users;
            $this->messageService->markAsRead($userId, $id);

            return $this->successResponse(null, 'Pesan ditandai sudah dibaca.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * POST /alumni/messages/conversations/{id}/typing
     * Broadcast typing indicator.
     */
    public function typing(Request $request, int $id)
    {
        $request->validate([
            'is_typing' => 'required|boolean',
        ]);

        try {
            $userId = auth()->user()->id_users;
            $this->messageService->broadcastTyping($userId, $id, $request->is_typing);

            return $this->successResponse(null);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    // =====================
    // STATS & CONTACTS
    // =====================

    /**
     * GET /alumni/messages/unread-count
     * Total unread message count across all conversations.
     */
    public function unreadCount()
    {
        try {
            $userId = auth()->user()->id_users;
            $count = $this->messageService->getTotalUnreadCount($userId);

            return $this->successResponse(['unread_count' => $count]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * GET /alumni/messages/contacts
     * Get list of alumni the user can message (accepted connections).
     */
    public function contacts(Request $request)
    {
        try {
            $userId = auth()->user()->id_users;
            $search = $request->input('search');
            $limit  = $request->input('limit', 20);

            $contacts = $this->messageService->getMessageableContacts($userId, $search, $limit);

            $data = $contacts->map(function ($alumni) {
                return [
                    'id_alumni'    => $alumni->id_alumni,
                    'id_users'     => $alumni->id_users,
                    'nama_alumni'  => $alumni->nama_alumni,
                    'foto'         => $alumni->foto,
                    'jurusan'      => $alumni->jurusan?->nama_jurusan ?? null,
                    'tahun_lulus'  => $alumni->tahun_lulus ? $alumni->tahun_lulus->format('Y') : null,
                ];
            });

            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil daftar kontak: ' . $e->getMessage());
        }
    }
}
