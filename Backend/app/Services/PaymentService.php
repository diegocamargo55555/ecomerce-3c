<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function pay(int $id, User $user): Order
    {
        return DB::transaction(function () use ($id, $user) {
            $order = Order::lockForUpdate()->findOrFail($id);
            abort_unless($order->users_id === $user->id, 403);
            abort_unless($order->status === 'pendente', 409, 'Este pedido não está aguardando pagamento.');
            $payment = Payment::where('order_id', $id)->lockForUpdate()->firstOrNew();
            abort_if($payment->exists && $payment->status !== 'pendente', 409, 'O pagamento deste pedido já foi processado.');
            $payment->fill(['order_id' => $id, 'amount' => $order->total, 'status' => 'pago', 'paid_at' => now()])->save();
            $order->update(['status' => 'pago']);

            return $order->load('items', 'address', 'payments');
        }, 3);
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
