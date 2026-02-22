<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpsiJawabanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_opsi,
            'opsi' => $this->opsi,
        ];
    }
}
