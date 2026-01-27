<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlatUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->user ?? $this;

        return [
            'id' => $this->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'address' => $user->address,
            'status' => $user->status,
            'phone' => $user->phone,
            'email' => $user->email,
            'document_number' => $user->document_number,
        ];
    }
}
