<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sender = $this->sender;
        $alumni = $sender?->alumni;

        $data = [
            'id_message'      => $this->id_message,
            'id_conversation' => $this->id_conversation,
            'type'            => $this->type,
            'body'            => $this->is_deleted ? null : $this->body,
            'file_url'        => $this->is_deleted ? null : $this->file_url,
            'file_name'       => $this->is_deleted ? null : $this->file_name,
            'file_mime'       => $this->file_mime,
            'file_size'       => $this->file_size,
            'is_deleted'      => $this->is_deleted,
            'created_at'      => $this->created_at?->toISOString(),
            'updated_at'      => $this->updated_at?->toISOString(),
            'sender' => [
                'id_users'     => $sender?->id_users,
                'id_alumni'    => $alumni?->id_alumni,
                'nama_alumni'  => $alumni?->nama_alumni ?? 'User',
                'foto'         => $alumni?->foto,
            ],
        ];

        // Reply-to info
        if ($this->reply_to_id && $this->whenLoaded('replyTo')) {
            $replyMsg = $this->replyTo;
            if ($replyMsg) {
                $replySender = $replyMsg->sender;
                $replyAlumni = $replySender?->alumni;
                $data['reply_to'] = [
                    'id_message'  => $replyMsg->id_message,
                    'body'        => $replyMsg->is_deleted ? 'Pesan telah dihapus' : $replyMsg->body,
                    'type'        => $replyMsg->type,
                    'sender'      => [
                        'nama_alumni' => $replyAlumni?->nama_alumni ?? 'User',
                    ],
                ];
            }
        }

        return $data;
    }
}
