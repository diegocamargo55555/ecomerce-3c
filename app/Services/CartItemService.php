<?php

namespace App\Services;

use App\Models\Cart_item;
use Illuminate\Database\Eloquent\Collection;

class CartItemService
{
    public function listAll(): Collection
    {
        return Cart_item::all();
    }

    public function create(array $data): Cart_item
    {
        return Cart_item::create($data);
    }

    public function findById(int $id): Cart_item
    {
        return Cart_item::findOrFail($id);
    }

    public function update(int $id, array $data): Cart_item
    {
        $cartItem = Cart_item::findOrFail($id);
        $cartItem->update($data);

        return $cartItem;
    }

    public function delete(int $id): void
    {
        Cart_item::findOrFail($id)->delete();
    }
}
