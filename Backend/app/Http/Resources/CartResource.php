<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'guest_token' => $this->when(! $this->relationLoaded('items'), $this->guest_token),
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'item_count' => $this->whenHas('item_count'),
            'total' => $this->whenHas('total'),
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}


/*
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'guest_token' => ['nullable', 'string', 'max:255', 'unique:carts,guest_token'],

*/
