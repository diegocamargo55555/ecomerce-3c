<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $userIds = DB::table('users')->pluck('id', 'email');
        $addressIds = DB::table('addresses')->pluck('id', 'users_id');

        $orders = [
            ['user' => 'test@example.com', 'status' => 'pago', 'subtotal' => '509.70', 'shipping_cost' => '20.00', 'total' => '529.70'],
            ['user' => 'ana@example.com', 'status' => 'pendente', 'subtotal' => '1499.90', 'shipping_cost' => '0.00', 'total' => '1499.90'],
            ['user' => 'bruno@example.com', 'status' => 'cancelado', 'subtotal' => '179.70', 'shipping_cost' => '15.00', 'total' => '194.70'],
        ];

        foreach ($orders as $order) {
            DB::table('orders')->insert([
                'address_id' => $addressIds[$userIds[$order['user']]],
                'users_id' => $userIds[$order['user']],
                'status' => $order['status'],
                'subtotal' => $order['subtotal'],
                'total' => $order['total'],
                'shipping_cost' => $order['shipping_cost'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
