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

    public function create(array $data): OrderItem
    {
        return OrderItem::create($data);
    }

    public function findById(int $id): OrderItem
    {
        return OrderItem::findOrFail($id);
    }

    public function update(int $id, array $data): OrderItem
    {
        $orderItem = OrderItem::findOrFail($id);
        $orderItem->update($data);

        return $orderItem;
    }

    public function delete(int $id): void
    {
        OrderItem::findOrFail($id)->delete();
    }
}
