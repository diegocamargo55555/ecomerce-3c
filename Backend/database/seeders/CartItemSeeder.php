<?php

namespace Database\Seeders;

use App\Models\Cart_item;
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
            ['user' => 'test@example.com', 'product' => 'Space Marines: Intercessors', 'quantity' => 1],
            ['user' => 'test@example.com', 'product' => 'Citadel Colour: Macragge Blue', 'quantity' => 2],
            ['user' => 'ana@example.com', 'product' => 'Necrons: Warriors', 'quantity' => 1],
            ['user' => 'bruno@example.com', 'product' => 'Citadel Starter Brush', 'quantity' => 3],
        ];

        foreach ($items as $item) {
            Cart_item::firstOrCreate([
                'cart_id' => $cartIds[$userIds[$item['user']]],
                'product_id' => $productIds[$item['product']],
            ], [
                'quantity' => $item['quantity'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Cart_item::firstOrCreate([
            'cart_id' => DB::table('carts')->where('guest_token', CartSeeder::guestToken())->value('id'),
            'product_id' => $productIds['Space Marines: Intercessors'],
        ], ['quantity' => 2]);
    }
}
