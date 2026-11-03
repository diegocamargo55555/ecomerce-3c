<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $categoryIds = DB::table('categories')->pluck('id', 'slug');

        $products = [
            ['category' => 'informatica', 'name' => 'Teclado Mecânico', 'description' => 'Teclado USB com iluminação e layout ABNT2.', 'price' => '249.90', 'stock' => 20, 'is_active' => true],
            ['category' => 'informatica', 'name' => 'Mouse Sem Fio', 'description' => 'Mouse sem fio com receptor USB.', 'price' => '129.90', 'stock' => 35, 'is_active' => true],
            ['category' => 'eletronicos', 'name' => 'Smartphone', 'description' => 'Smartphone com 128 GB de armazenamento.', 'price' => '1499.90', 'stock' => 10, 'is_active' => true],
            ['category' => 'eletronicos', 'name' => 'Fone Bluetooth', 'description' => 'Fone de ouvido Bluetooth com estojo carregador.', 'price' => '199.90', 'stock' => 25, 'is_active' => true],
            ['category' => 'roupas', 'name' => 'Camiseta Básica', 'description' => 'Camiseta de algodão, tamanho M.', 'price' => '59.90', 'stock' => 50, 'is_active' => true],
            ['category' => 'roupas', 'name' => 'Jaqueta Jeans', 'description' => 'Jaqueta jeans, tamanho G.', 'price' => '189.90', 'stock' => 0, 'is_active' => false],
        ];

        foreach ($products as $product) {
            DB::table('products')->insert([
                'category_id' => $categoryIds[$product['category']],
                'name' => $product['name'],
                'description' => $product['description'],
                'price' => $product['price'],
                'stock' => $product['stock'],
                'image_url' => null,
                'is_active' => $product['is_active'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
