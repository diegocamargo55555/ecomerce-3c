<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $images = [
            'Space Marines: Intercessors' => 'space-marines-intercessors.jpg',
            'Adepta Sororitas: Battle Sisters Squad' => 'battle-sisters-squad.jpg',
            'Chaos Space Marines: Legionaries' => 'chaos-space-marines-legionaries.jpg',
            'Death Guard: Plague Marines' => 'death-guard-plague-marines.jpg',
            'Necrons: Warriors' => 'necron-warriors.jpg',
            'Tyranids: Termagants' => 'tyranid-termagants.jpg',
            'Citadel Colour: Macragge Blue' => 'citadel-macragge-blue.jpg',
            'Citadel Starter Brush' => 'citadel-starter-brush.jpg',
        ];

        DB::transaction(function () use ($images) {
            foreach ($images as $name => $image) {
                DB::table('products')
                    ->where('name', $name)
                    ->where(fn ($query) => $query->whereNull('image_url')->orWhere('image_url', ''))
                    ->update(['image_url' => asset('images/products/'.$image)]);
            }
        });
    }

    public function down(): void
    {
        // Preserve photos on rollback because their previous ownership is unknown.
    }
};
