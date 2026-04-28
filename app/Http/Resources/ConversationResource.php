<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    /**
     * The authenticated user's ID (set before collection transform).
     */
    private ?int $currentUserId;

    public function __construct($resource, ?int $currentUserId = null)
    {
        parent::__construct($resource);
        $this->currentUserId = $currentUserId;
    }

    /**
     * Create a collection with the current user context.
     */
    public static function collectionWithUser($resource, int $userId)
    {
        return $resource->map(function ($item) use ($userId) {
            return new static($item, $userId);
        });
    }

    public function toArray(Request $request): array
    {
        $userId = $this->currentUserId ?? $request->user()?->id_users;

        $data = [
            'id_conversation' => $this->id_conversation,
            'type'            => $this->type,
            'group_name'      => $this->group_name,
            'group_avatar'    => $this->group_avatar,
            'created_at'      => $this->created_at?->toISOString(),
            'unread_count'    => $this->unread_count ?? 0,
        ];

        // For private conversations — resolve the other participant as "contact"
        if ($this->type === 'private' && $userId) {
            $otherParticipant = $this->activeParticipants
                ?->first(fn($p) => (int) $p->id_users !== $userId);

            if ($otherParticipant) {
                $alumni = $otherParticipant->user?->alumni;
                $data['contact'] = [
                    'id_users'     => $otherParticipant->id_users,
                    'id_alumni'    => $alumni?->id_alumni,
                    'nama_alumni'  => $alumni?->nama_alumni ?? 'User',
                    'foto'         => $alumni?->foto,
                    'jurusan'      => $alumni?->jurusan?->nama_jurusan ?? null,
                    'tahun_lulus'  => $alumni?->tahun_lulus ? $alumni->tahun_lulus->format('Y') : null,
                    'last_read_at' => $otherParticipant->last_read_at?->toISOString(),
                ];
            }
        }

        // For group — include all participants
        if ($this->type === 'group') {
            $data['participants'] = $this->activeParticipants?->map(function ($p) {
                $alumni = $p->user?->alumni;
                return [
                    'id_users'     => $p->id_users,
                    'id_alumni'    => $alumni?->id_alumni,
                    'nama_alumni'  => $alumni?->nama_alumni ?? 'User',
                    'foto'         => $alumni?->foto,
                    'role'         => $p->role,
                    'last_read_at' => $p->last_read_at?->toISOString(),
                ];
            }) ?? [];
        }

        // Participant settings for the current user
        $myParticipant = $this->participants
            ?->first(fn($p) => (int) $p->id_users === $userId && $p->left_at === null);

        if ($myParticipant) {
            $data['settings'] = [
                'is_pinned'   => (bool) $myParticipant->is_pinned,
                'is_muted'    => (bool) $myParticipant->is_muted,
                'is_archived' => (bool) $myParticipant->is_archived,
                'last_read_at'=> $myParticipant->last_read_at?->toISOString(),
            ];
        }

        // Latest message preview
        if ($this->relationLoaded('latestMessage') && $this->latestMessage) {
            $msg = $this->latestMessage;
            $sender = $msg->sender;
            $senderAlumni = $sender?->alumni;

            $data['last_message'] = [
                'id_message'  => $msg->id_message,
                'type'        => $msg->type,
                'body'        => $msg->is_deleted ? 'Pesan telah dihapus' : $msg->body,
                'file_name'   => $msg->file_name,
                'created_at'  => $msg->created_at?->toISOString(),
                'sender'      => [
                    'id_users'    => $sender?->id_users,
                    'nama_alumni' => $senderAlumni?->nama_alumni ?? 'User',
                ],
            ];
        }

        return $data;
    }
}
