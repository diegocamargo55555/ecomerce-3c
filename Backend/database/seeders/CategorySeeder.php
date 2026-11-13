<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $categories = [
            ['name' => 'Imperium', 'slug' => 'imperium', 'image' => 'space-marines-intercessors.jpg', 'description' => 'Miniaturas de Space Marines, Adepta Sororitas e outros exércitos do Imperium para Warhammer 40.000.'],
            ['name' => 'Caos', 'slug' => 'caos', 'image' => 'chaos-space-marines-legionaries.jpg', 'description' => 'Miniaturas das legiões e forças do Caos para Warhammer 40.000.'],
            ['name' => 'Xenos', 'slug' => 'xenos', 'image' => 'necron-warriors.jpg', 'description' => 'Miniaturas de Necrons, Tyranids e outras facções alienígenas de Warhammer 40.000.'],
            ['name' => 'Pintura e acessórios', 'slug' => 'pintura-e-acessorios', 'image' => 'citadel-macragge-blue.jpg', 'description' => 'Tintas Citadel e ferramentas para preparar e pintar suas miniaturas.'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['slug' => $category['slug']], [
                'name' => $category['name'],
                'slug' => $category['slug'],
                'description' => $category['description'],
                'image_url' => '/images/products/'.$category['image'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
