<?php

namespace Database\Seeders;

use App\Models\Payment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $userIds = DB::table('users')->pluck('id', 'email');
        $orders = DB::table('orders')->get();

        $payments = [
            ['user' => 'test@example.com', 'order_status' => 'pago', 'status' => 'pago', 'amount' => '439.70', 'paid_at' => $now],
            ['user' => 'ana@example.com', 'order_status' => 'pendente', 'status' => 'pendente', 'amount' => '299.90', 'paid_at' => null],
            ['user' => 'bruno@example.com', 'order_status' => 'cancelado', 'status' => 'cancelado', 'amount' => '164.70', 'paid_at' => null],
            ['user' => 'test@example.com', 'order_status' => 'enviado', 'status' => 'pago', 'amount' => '369.90', 'paid_at' => $now],
            ['user' => 'ana@example.com', 'order_status' => 'entregue', 'status' => 'pago', 'amount' => '69.80', 'paid_at' => $now],
        ];

        foreach ($payments as $payment) {
            Payment::firstOrCreate([
                'order_id' => $orders->where('users_id', $userIds[$payment['user']])->firstWhere('status', $payment['order_status'])->id,
            ], [
                'status' => $payment['status'],
                'amount' => $payment['amount'],
                'paid_at' => $payment['paid_at'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
