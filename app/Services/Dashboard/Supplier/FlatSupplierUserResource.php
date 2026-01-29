<?php

namespace App\Services\Dashboard\Supplier;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

//Aplana todos los elementos que vienen de Supplier y los fusiona con los del user

class FlatSupplierUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->user ?? $this;

        // Log::info($this->addresses);

        return [
            'id' => $this->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'status' => $user->status,
            'phone' => $user->phone,
            'email' => $user->email,
            'document_number' => $user->document_number,
        ];
    }
}
