<?php

namespace App\Services\Dashboard\Courier;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

//Aplana todos los elementos que vienen de courier y los fusiona con los del user

class FlatCourierUserResource extends JsonResource
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
            'is_cash_on_delivery' => $this->is_cash_on_delivery,
            'is_freight_collect'  => $this->is_freight_collect,
        ];
    }
}
