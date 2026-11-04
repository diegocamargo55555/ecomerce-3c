<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $userIds = DB::table('users')->pluck('id', 'email');
        $orderIds = DB::table('orders')->pluck('id', 'users_id');

        $payments = [
            ['user' => 'test@example.com', 'status' => 'pago', 'amount' => '529.70', 'paid_at' => $now],
            ['user' => 'ana@example.com', 'status' => 'pendente', 'amount' => '1499.90', 'paid_at' => null],
            ['user' => 'bruno@example.com', 'status' => 'cancelado', 'amount' => '194.70', 'paid_at' => null],
        ];

        foreach ($payments as $payment) {
            DB::table('payments')->insert([
                'order_id' => $orderIds[$userIds[$payment['user']]],
                'status' => $payment['status'],
                'amount' => $payment['amount'],
                'paid_at' => $payment['paid_at'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
