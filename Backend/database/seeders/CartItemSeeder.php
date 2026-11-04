<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CartItemSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $userIds = DB::table('users')->pluck('id', 'email');
        $cartIds = DB::table('carts')->pluck('id', 'user_id');
        $productIds = DB::table('products')->pluck('id', 'name');

        $items = [
            ['user' => 'test@example.com', 'product' => 'Teclado Mecânico', 'quantity' => 1],
            ['user' => 'test@example.com', 'product' => 'Mouse Sem Fio', 'quantity' => 2],
            ['user' => 'ana@example.com', 'product' => 'Smartphone', 'quantity' => 1],
            ['user' => 'bruno@example.com', 'product' => 'Camiseta Básica', 'quantity' => 3],
        ];

        foreach ($items as $item) {
            DB::table('cart_items')->insert([
                'cart_id' => $cartIds[$userIds[$item['user']]],
                'product_id' => $productIds[$item['product']],
                'quantity' => $item['quantity'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
