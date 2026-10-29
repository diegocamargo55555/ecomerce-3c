<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;

class OrderService
{
    public function listAll(): Collection
    {
        return Order::all();
    }

    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function findById(int $id): Order
    {
        return Order::findOrFail($id);
    }

    public function update(int $id, array $data): Order
    {
        $order = Order::findOrFail($id);
        $order->update($data);

        return $order;
    }

    public function delete(int $id): void
    {
        Order::findOrFail($id)->delete();
    }
}