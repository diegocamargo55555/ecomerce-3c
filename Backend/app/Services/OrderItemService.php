<?php

namespace App\Services;

use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Collection;

class OrderItemService
{
    public function listAll(): Collection
    {
        return OrderItem::all();
    }

    public function findById(int $id): OrderItem
    {
        return OrderItem::findOrFail($id);
    }
}
