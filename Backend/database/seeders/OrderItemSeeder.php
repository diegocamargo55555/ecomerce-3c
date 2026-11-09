<?php

namespace Database\Seeders;

use App\Models\OrderItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderItemSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $userIds = DB::table('users')->pluck('id', 'email');
        $orders = DB::table('orders')->get();
        $productIds = DB::table('products')->pluck('id', 'name');

        $items = [
            ['user' => 'test@example.com', 'status' => 'pago', 'product' => 'Space Marines: Intercessors', 'unit_price' => '349.90', 'quantity' => 1, 'subtotal' => '349.90'],
            ['user' => 'test@example.com', 'status' => 'pago', 'product' => 'Citadel Colour: Macragge Blue', 'unit_price' => '34.90', 'quantity' => 2, 'subtotal' => '69.80'],
            ['user' => 'ana@example.com', 'status' => 'pendente', 'product' => 'Necrons: Warriors', 'unit_price' => '299.90', 'quantity' => 1, 'subtotal' => '299.90'],
            ['user' => 'bruno@example.com', 'status' => 'cancelado', 'product' => 'Citadel Starter Brush', 'unit_price' => '49.90', 'quantity' => 3, 'subtotal' => '149.70'],
            ['user' => 'test@example.com', 'status' => 'enviado', 'product' => 'Space Marines: Intercessors', 'unit_price' => '349.90', 'quantity' => 1, 'subtotal' => '349.90'],
            ['user' => 'ana@example.com', 'status' => 'entregue', 'product' => 'Citadel Colour: Macragge Blue', 'unit_price' => '34.90', 'quantity' => 2, 'subtotal' => '69.80'],
        ];

        foreach ($items as $item) {
            OrderItem::firstOrCreate([
                'order_id' => $orders->where('users_id', $userIds[$item['user']])->firstWhere('status', $item['status'])->id,
                'product_id' => $productIds[$item['product']],
            ], [
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
