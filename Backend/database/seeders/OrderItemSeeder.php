<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderItemSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $userIds = DB::table('users')->pluck('id', 'email');
        $orderIds = DB::table('orders')->pluck('id', 'users_id');
        $productIds = DB::table('products')->pluck('id', 'name');

        $items = [
            ['user' => 'test@example.com', 'product' => 'Space Marines: Intercessors', 'unit_price' => '349.90', 'quantity' => 1, 'subtotal' => '349.90'],
            ['user' => 'test@example.com', 'product' => 'Citadel Colour: Macragge Blue', 'unit_price' => '34.90', 'quantity' => 2, 'subtotal' => '69.80'],
            ['user' => 'ana@example.com', 'product' => 'Necrons: Warriors', 'unit_price' => '299.90', 'quantity' => 1, 'subtotal' => '299.90'],
            ['user' => 'bruno@example.com', 'product' => 'Citadel Starter Brush', 'unit_price' => '49.90', 'quantity' => 3, 'subtotal' => '149.70'],
        ];

        foreach ($items as $item) {
            DB::table('order_items')->insert([
                'order_id' => $orderIds[$userIds[$item['user']]],
                'product_id' => $productIds[$item['product']],
                'product_name' => $item['product'],
                'unit_price' => $item['unit_price'],
                'quantity' => $item['quantity'],
                'subtotal' => $item['subtotal'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
