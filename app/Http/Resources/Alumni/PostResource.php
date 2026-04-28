<?php

namespace App\Http\Resources\Alumni;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id_post'    => $this->id_post,
            'content'    => $this->content,
            'visibility' => $this->visibility,
            'author'     => [
                'id_alumni'     => $this->alumni->id_alumni ?? null,
                'nama_alumni'   => $this->alumni->nama_alumni ?? null,
                'foto'          => $this->alumni->foto
                    ? (str_starts_with($this->alumni->foto, 'http')
                        ? $this->alumni->foto
                        : Storage::disk('public')->url($this->alumni->foto))
                    : null,
                'jurusan'       => $this->alumni->jurusan->nama_jurusan ?? null,
            ],
            'images'         => $this->whenLoaded('images', function () {
                return $this->images->map(fn($img) => [
                    'id_post_image' => $img->id_post_image,
                    'url'           => Storage::disk('public')->url($img->image_path),
                    'sort_order'    => $img->sort_order,
                ]);
            }),
            'likes_count'    => $this->likes_count ?? 0,
            'comments_count' => $this->comments_count ?? 0,
            'is_liked'       => (bool) ($this->is_liked ?? false),
            'is_own_post'    => $this->when(
                auth()->check(),
                function () {
                    $user = auth()->user();
                    $alumni = $user->alumni ?? null;
                    return $alumni ? $this->id_alumni === $alumni->id_alumni : false;
                }
            ),
            'created_at'     => $this->created_at?->toISOString(),
            'updated_at'     => $this->updated_at?->toISOString(),
        ];
    }
}
