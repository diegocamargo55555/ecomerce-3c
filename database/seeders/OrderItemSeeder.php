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
            ['user' => 'test@example.com', 'product' => 'Teclado Mecânico', 'unit_price' => '249.90', 'quantity' => 1, 'subtotal' => '249.90'],
            ['user' => 'test@example.com', 'product' => 'Mouse Sem Fio', 'unit_price' => '129.90', 'quantity' => 2, 'subtotal' => '259.80'],
            ['user' => 'ana@example.com', 'product' => 'Smartphone', 'unit_price' => '1499.90', 'quantity' => 1, 'subtotal' => '1499.90'],
            ['user' => 'bruno@example.com', 'product' => 'Camiseta Básica', 'unit_price' => '59.90', 'quantity' => 3, 'subtotal' => '179.70'],
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
