<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

    public function create(array $data): OrderItem
    {
        return DB::transaction(function () use ($data) {
            $order = $this->pendingOrder($data['order_id']);
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);
            if (! $product->is_active || $data['quantity'] > $product->stock) {
                throw ValidationException::withMessages(['quantity' => ['Produto indisponível ou estoque insuficiente.']]);
            }
            $subtotal = bcmul($product->price, (string) $data['quantity'], 2);
            $this->recalculate($order, $subtotal);
            $item = $order->items()->create([
                'product_id' => $product->id, 'product_name' => $product->name,
                'unit_price' => $product->price, 'quantity' => $data['quantity'],
                'subtotal' => $subtotal,
            ]);
            $product->decrement('stock', $item->quantity);

            return $item;
        }, 3);
    }

    public function update(int $id, int $quantity): OrderItem
    {
        return DB::transaction(function () use ($id, $quantity) {
            $order = $this->pendingOrder(OrderItem::findOrFail($id)->order_id);
            $item = $order->items()->lockForUpdate()->findOrFail($id);
            $product = Product::withTrashed()->lockForUpdate()->findOrFail($item->product_id);
            $increase = $quantity - $item->quantity;
            if ($increase > 0 && ($product->trashed() || ! $product->is_active || $increase > $product->stock)) {
                throw ValidationException::withMessages(['quantity' => ['Produto indisponível ou estoque insuficiente.']]);
            }
            $subtotal = bcmul($item->unit_price, (string) $quantity, 2);
            $this->recalculate($order, $subtotal, $item->id);
            $product->decrement('stock', $increase);
            $item->update(['quantity' => $quantity, 'subtotal' => $subtotal]);

            return $item;
        }, 3);
    }

    private function pendingOrder(int $id): Order
    {
        $order = Order::lockForUpdate()->findOrFail($id);
        abort_unless($order->status === 'pendente', 409, 'Somente pedidos pendentes permitem alterar itens.');
        $payment = $order->payments()->lockForUpdate()->first();
        abort_if($payment && $payment->status !== 'pendente', 409, 'O pagamento deste pedido já foi processado.');

        return $order;
    }

    private function recalculate(Order $order, string $lineSubtotal, ?int $replacedItemId = null): void
    {
        $subtotal = $lineSubtotal;
        foreach ($order->items()->orderBy('product_id')->lockForUpdate()->get() as $item) {
            if ($item->id !== $replacedItemId) {
                $subtotal = bcadd($subtotal, $item->subtotal, 2);
            }
        }
        $total = bcadd($subtotal, $order->shipping_cost, 2);
        if (bccomp($total, '99999999.99', 2) > 0) {
            throw ValidationException::withMessages(['quantity' => ['O total ultrapassa o limite de R$ 99.999.999,99 por pedido.']]);
        }
        $order->update(['subtotal' => $subtotal, 'total' => $total]);
        $payment = $order->payments()->lockForUpdate()->firstOrNew();
        $payment->fill(['status' => 'pendente', 'amount' => $total, 'paid_at' => null])->save();
    }

}
