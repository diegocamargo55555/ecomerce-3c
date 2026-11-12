<?php

namespace Database\Seeders;

use App\Models\Product;
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

        array_push($products,
            ['category' => 'imperium', 'name' => 'Space Marines: Terminator Assault Squad', 'image' => 'space-marines-terminator-assault-squad.jpg', 'description' => 'Esquadrão de Terminators de assalto com martelos e garras, a infantaria de elite mais resistente dos Space Marines.', 'price' => '399.90', 'stock' => 7, 'is_active' => true],
            ['category' => 'imperium', 'name' => 'Space Marines: Redemptor Dreadnought', 'image' => 'space-marines-redemptor-dreadnought.jpg', 'description' => 'Caminhante de combate pesado dos Space Marines, armado para dar apoio de fogo à linha de frente.', 'price' => '449.90', 'stock' => 5, 'is_active' => true],
            ['category' => 'imperium', 'name' => 'Astra Militarum: Cadian Shock Troops', 'image' => 'cadian-shock-troops.jpg', 'description' => 'Soldados de infantaria da Guarda Imperial, a base numerosa de qualquer exército do Astra Militarum.', 'price' => '329.90', 'stock' => 14, 'is_active' => true],
            ['category' => 'imperium', 'name' => 'Adeptus Custodes: Custodian Guard Sodality', 'image' => 'custodian-guard-sodality.jpg', 'description' => 'Guardiões dourados do Imperador, poucos em número e devastadores em combate.', 'price' => '379.90', 'stock' => 6, 'is_active' => true],
            ['category' => 'imperium', 'name' => 'Adeptus Mechanicus: Skitarii', 'image' => 'adeptus-mechanicus-skitarii.jpg', 'description' => 'Soldados ciborgues do Culto Mechanicus, montáveis como Rangers ou Vanguard.', 'price' => '349.90', 'stock' => 9, 'is_active' => true],
            ['category' => 'imperium', 'name' => 'Grey Knights: Strike Squad', 'image' => 'grey-knights-strike-squad.jpg', 'description' => 'Caçadores de demônios dos Grey Knights, guerreiros psíquicos em armadura prateada.', 'price' => '339.90', 'stock' => 4, 'is_active' => true],
            ['category' => 'caos', 'name' => 'Chaos Space Marines: Chaos Terminators', 'image' => 'chaos-space-marines-terminators.jpg', 'description' => 'Veteranos traidores em armadura Terminator, a ponta de lança das legiões do Caos.', 'price' => '399.90', 'stock' => 5, 'is_active' => true],
            ['category' => 'caos', 'name' => 'World Eaters: Khorne Berzerkers', 'image' => 'world-eaters-khorne-berzerkers.jpg', 'description' => 'Guerreiros enlouquecidos devotos de Khorne, especialistas em combate corpo a corpo.', 'price' => '369.90', 'stock' => 8, 'is_active' => true],
            ['category' => 'caos', 'name' => 'Thousand Sons: Rubric Marines', 'image' => 'thousand-sons-rubric-marines.jpg', 'description' => 'Armaduras animadas por feitiçaria a serviço de Tzeentch, lideradas por um feiticeiro aspirante.', 'price' => '389.90', 'stock' => 3, 'is_active' => true],
            ['category' => 'caos', 'name' => 'Chaos Space Marines: Forgefiend', 'image' => 'chaos-space-marines-forgefiend.jpg', 'description' => 'Máquina demoníaca quadrúpede que despeja fogo pesado sobre o inimigo.', 'price' => '279.90', 'stock' => 11, 'is_active' => true],
            ['category' => 'caos', 'name' => 'Death Guard: Poxwalkers', 'image' => 'death-guard-poxwalkers.jpg', 'description' => 'Horda de mortos-vivos infectados que avança à frente da Death Guard.', 'price' => '249.90', 'stock' => 0, 'is_active' => true],
            ['category' => 'xenos', 'name' => 'Orks: Boyz', 'image' => 'orks-boyz.jpg', 'description' => 'Bando de guerreiros Orks, barulhentos e numerosos, o coração de qualquer Waaagh!', 'price' => '289.90', 'stock' => 16, 'is_active' => true],
            ['category' => 'xenos', 'name' => 'Aeldari: Guardians', 'image' => 'aeldari-guardians.jpg', 'description' => 'Milícia cidadã dos mundos-nave Aeldari, com plataforma de arma pesada.', 'price' => '319.90', 'stock' => 7, 'is_active' => true],
            ['category' => 'xenos', 'name' => "T'au Empire: Fire Warriors", 'image' => 'tau-fire-warriors.jpg', 'description' => "Infantaria da casta do fogo do Império T'au, com rifles de pulso de longo alcance.", 'price' => '339.90', 'stock' => 10, 'is_active' => true],
            ['category' => 'xenos', 'name' => 'Drukhari: Kabalite Warriors', 'image' => 'drukhari-kabalite-warriors.jpg', 'description' => 'Corsários cruéis de Commorragh, rápidos e armados com rifles de estilhaços.', 'price' => '299.90', 'stock' => 6, 'is_active' => true],
            ['category' => 'xenos', 'name' => 'Genestealer Cults: Atalan Jackals', 'image' => 'genestealer-cults-atalan-jackals.jpg', 'description' => 'Batedores do culto em motos e quadriciclos, rápidos para flanquear o inimigo.', 'price' => '309.90', 'stock' => 5, 'is_active' => true],
            ['category' => 'xenos', 'name' => 'Necrons: Immortals', 'image' => 'necron-immortals.jpg', 'description' => 'Tropas de choque Necrons, mais resistentes e mais bem armadas que os Warriors.', 'price' => '329.90', 'stock' => 8, 'is_active' => true],
            ['category' => 'xenos', 'name' => 'Tyranids: Hormagaunts', 'image' => 'tyranid-hormagaunts.jpg', 'description' => 'Predadores velozes do enxame Tyranid, feitos para alcançar o inimigo em um salto.', 'price' => '259.90', 'stock' => 13, 'is_active' => true],
            ['category' => 'pintura-e-acessorios', 'name' => 'Citadel Colour: Mephiston Red', 'image' => 'citadel-mephiston-red.jpg', 'description' => 'Tinta base vermelha da linha Citadel Colour, de alta cobertura.', 'price' => '34.90', 'stock' => 40, 'is_active' => true],
            ['category' => 'pintura-e-acessorios', 'name' => 'Citadel Colour: Nuln Oil', 'image' => 'citadel-nuln-oil.jpg', 'description' => 'Tinta shade escura da linha Citadel Colour para sombrear recessos e metais.', 'price' => '44.90', 'stock' => 30, 'is_active' => true],
            ['category' => 'pintura-e-acessorios', 'name' => 'Citadel Colour Spray Stick', 'image' => 'citadel-colour-spray-stick.jpg', 'description' => 'Suporte para prender várias miniaturas e aplicar primer em spray de uma só vez.', 'price' => '129.90', 'stock' => 9, 'is_active' => true],
            ['category' => 'imperium', 'name' => 'Space Marines: Eradicator Squad with Heavy Bolters', 'image' => 'space-marines-eradicators-heavy-bolters.jpg', 'description' => 'Três Space Marines em armadura Gravis armados com bolters pesados, para apoio de fogo de curto alcance.', 'price' => '319.90', 'stock' => 2, 'is_active' => true],
        );

        foreach ($products as $product) {
            Product::firstOrCreate(['name' => $product['name']], [
                'category_id' => $categoryIds[$product['category']],
                'name' => $product['name'],
                'description' => $product['description'],
                'price' => $product['price'],
                'stock' => $product['stock'],
                'image_url' => '/images/products/'.$product['image'],
                'is_active' => $product['is_active'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
