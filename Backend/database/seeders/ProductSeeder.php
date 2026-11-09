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

        // Preços e estoques ilustrativos para demonstração.
        $products = [
            ['category' => 'imperium', 'name' => 'Space Marines: Intercessors', 'image' => 'space-marines-intercessors.jpg', 'description' => 'Esquadrão de miniaturas de Space Marines para montar e pintar seu exército de Warhammer 40.000.', 'price' => '349.90', 'stock' => 20, 'is_active' => true],
            ['category' => 'imperium', 'name' => 'Adepta Sororitas: Battle Sisters Squad', 'image' => 'battle-sisters-squad.jpg', 'description' => 'Miniaturas das guerreiras da Adepta Sororitas para compor suas forças do Imperium em Warhammer 40.000.', 'price' => '399.90', 'stock' => 8, 'is_active' => true],
            ['category' => 'caos', 'name' => 'Chaos Space Marines: Legionaries', 'image' => 'chaos-space-marines-legionaries.jpg', 'description' => 'Miniaturas de legionários dos Chaos Space Marines para montar e pintar suas forças do Caos.', 'price' => '379.90', 'stock' => 4, 'is_active' => true],
            ['category' => 'caos', 'name' => 'Death Guard: Plague Marines', 'image' => 'death-guard-plague-marines.jpg', 'description' => 'Miniaturas de Plague Marines da Death Guard para sua coleção de Warhammer 40.000.', 'price' => '329.90', 'stock' => 0, 'is_active' => false],
            ['category' => 'xenos', 'name' => 'Necrons: Warriors', 'image' => 'necron-warriors.jpg', 'description' => 'Miniaturas de guerreiros Necrons para montar e pintar seu exército de Warhammer 40.000.', 'price' => '299.90', 'stock' => 12, 'is_active' => true],
            ['category' => 'xenos', 'name' => 'Tyranids: Termagants', 'image' => 'tyranid-termagants.jpg', 'description' => 'Miniaturas de Termagants para ampliar seu enxame Tyranid em Warhammer 40.000.', 'price' => '259.90', 'stock' => 15, 'is_active' => true],
            ['category' => 'pintura-e-acessorios', 'name' => 'Citadel Colour: Macragge Blue', 'image' => 'citadel-macragge-blue.jpg', 'description' => 'Tinta azul da linha Citadel Colour para a pintura de suas miniaturas de Warhammer 40.000.', 'price' => '34.90', 'stock' => 50, 'is_active' => true],
            ['category' => 'pintura-e-acessorios', 'name' => 'Citadel Starter Brush', 'image' => 'citadel-starter-brush.jpg', 'description' => 'Pincel Citadel para começar a pintar suas miniaturas de Warhammer 40.000.', 'price' => '49.90', 'stock' => 3, 'is_active' => true],
        ];

        foreach ($products as $product) {
            DB::table('products')->insert([
                'category_id' => $categoryIds[$product['category']],
                'name' => $product['name'],
                'description' => $product['description'],
                'price' => $product['price'],
                'stock' => $product['stock'],
                'image_url' => asset('images/products/'.$product['image']),
                'is_active' => $product['is_active'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
