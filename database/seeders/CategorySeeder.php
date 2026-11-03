<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $categories = [
            ['name' => 'Informática', 'slug' => 'informatica', 'description' => 'Computadores e acessórios para o dia a dia.'],
            ['name' => 'Eletrônicos', 'slug' => 'eletronicos', 'description' => 'Celulares, fones e outros dispositivos.'],
            ['name' => 'Roupas', 'slug' => 'roupas', 'description' => 'Roupas e acessórios de vestuário.'],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->insert([
                'name' => $category['name'],
                'slug' => $category['slug'],
                'description' => $category['description'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
