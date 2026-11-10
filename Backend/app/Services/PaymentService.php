<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(private OrderService $orderService) {}

    public function pay(int $id, User $user): Order
    {
        return DB::transaction(function () use ($id, $user) {
            $order = Order::lockForUpdate()->findOrFail($id);
            abort_unless($order->users_id === $user->id, 403);

            return $this->confirm($order);
        }, 3);
    }

    public function create(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $order = Order::lockForUpdate()->findOrFail($data['order_id']);
            abort_unless($order->status === 'pendente', 409, 'Este pedido não está aguardando pagamento.');
            abort_if($order->payments()->exists(), 409, 'Este pedido já possui um pagamento.');

            return $order->payments()->create(['status' => 'pendente', 'amount' => $order->total]);
        }, 3);
    }

    public function update(int $id, string $status, User $user): Payment
    {
        return DB::transaction(function () use ($id, $status, $user) {
            $payment = Payment::findOrFail($id);
            $order = Order::lockForUpdate()->findOrFail($payment->order_id);
            if ($status === 'cancelado') {
                $this->orderService->cancel($order->id, $user);
            } else {
                abort_unless($status === 'pago', 409, 'Transição de pagamento inválida.');
                $this->confirm($order);
            }

            return $payment->refresh();
        }, 3);
    }

    private function confirm(Order $order): Order
    {
        abort_unless($order->status === 'pendente', 409, 'Este pedido não está aguardando pagamento.');
        $payment = Payment::where('order_id', $order->id)->lockForUpdate()->firstOrNew();
        abort_if($payment->exists && $payment->status !== 'pendente', 409, 'O pagamento deste pedido já foi processado.');
        $payment->fill(['order_id' => $order->id, 'amount' => $order->total, 'status' => 'pago', 'paid_at' => now()])->save();
        $order->update(['status' => 'pago']);

        return $order->load('items', 'address', 'payments');
    }

    public function listAll(): Collection
    {
        return Payment::all();
    }

    public function findById(int $id): Payment
    {
        return Payment::findOrFail($id);
    }

}
