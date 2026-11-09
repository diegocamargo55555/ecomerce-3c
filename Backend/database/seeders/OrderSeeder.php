<?php

namespace Database\Seeders;

use App\Models\Order;
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
            ['user' => 'test@example.com', 'status' => 'pago', 'subtotal' => '419.70', 'shipping_cost' => '20.00', 'total' => '439.70'],
            ['user' => 'ana@example.com', 'status' => 'pendente', 'subtotal' => '299.90', 'shipping_cost' => '0.00', 'total' => '299.90'],
            ['user' => 'bruno@example.com', 'status' => 'cancelado', 'subtotal' => '149.70', 'shipping_cost' => '15.00', 'total' => '164.70'],
            ['user' => 'test@example.com', 'status' => 'enviado', 'subtotal' => '349.90', 'shipping_cost' => '20.00', 'total' => '369.90'],
            ['user' => 'ana@example.com', 'status' => 'entregue', 'subtotal' => '69.80', 'shipping_cost' => '0.00', 'total' => '69.80'],
        ];

        foreach ($orders as $order) {
            Order::firstOrCreate([
                'users_id' => $userIds[$order['user']],
                'status' => $order['status'],
            ], [
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
