<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id_users,
            'email' => $this->email_users,
            'role' => $this->role,
            'profile' => new AlumniResource($this->whenLoaded('alumni')),
            'admin_profile' => new AdminResource($this->whenLoaded('admin')),
            'created_at' => $this->created_at,
        ];

        // Add can_access_all if available (from login response)
        if (isset($this->can_access_all)) {
            $data['can_access_all'] = $this->can_access_all;
        }

        return $data;
    }
}
