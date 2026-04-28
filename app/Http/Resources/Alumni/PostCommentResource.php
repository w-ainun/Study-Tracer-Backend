<?php

namespace App\Http\Resources\Alumni;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PostCommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id_comment'        => $this->id_comment,
            'id_post'           => $this->id_post,
            'id_parent_comment' => $this->id_parent_comment,
            'content'           => $this->content,
            'author'            => [
                'id_alumni'   => $this->alumni->id_alumni ?? null,
                'nama_alumni' => $this->alumni->nama_alumni ?? null,
                'foto'        => $this->alumni->foto
                    ? (str_starts_with($this->alumni->foto, 'http')
                        ? $this->alumni->foto
                        : Storage::disk('public')->url($this->alumni->foto))
                    : null,
                'jurusan'     => $this->alumni->jurusan->nama_jurusan ?? null,
            ],
            'replies_count' => $this->replies_count ?? 0,
            'is_own_comment' => $this->when(
                auth()->check(),
                function () {
                    $user = auth()->user();
                    $alumni = $user->alumni ?? null;
                    return $alumni ? $this->id_alumni === $alumni->id_alumni : false;
                }
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
