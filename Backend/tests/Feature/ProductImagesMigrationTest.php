<?php

namespace Tests\Feature;

use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductImagesMigrationTest extends TestCase
{
    public function test_it_fills_missing_photos_without_changing_existing_photos_or_other_data(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null,
        ]);
        $this->artisan('migrate', ['--database' => 'sqlite', '--force' => true])->assertSuccessful();
        $this->seed([CategorySeeder::class, ProductSeeder::class]);

        $expectedImages = DB::table('products')->orderBy('id')->pluck('image_url', 'id');
        DB::table('products')->update(['image_url' => null]);
        $ids = $expectedImages->keys();
        DB::table('products')->where('id', $ids[0])->update(['image_url' => '']);
        $customImage = 'https://example.test/custom-photo.jpg';
        DB::table('products')->where('id', $ids[1])->update(['image_url' => $customImage]);
        $expectedImages[$ids[1]] = $customImage;

        $unknownProduct = (array) DB::table('products')->first();
        unset($unknownProduct['id']);
        $unknownId = DB::table('products')->insertGetId([
            ...$unknownProduct,
            'name' => 'Produto personalizado',
            'image_url' => null,
        ]);
        $before = DB::table('products')->orderBy('id')->get()->keyBy('id');

        $migration = require database_path('migrations/2026_11_08_230858_fill_missing_product_images.php');
        $migration->up();

        foreach ($expectedImages as $id => $imageUrl) {
            $product = DB::table('products')->find($id);
            $this->assertSame($imageUrl, $product->image_url);
            $original = (array) $before[$id];
            $updated = (array) $product;
            unset($original['image_url'], $updated['image_url']);
            $this->assertSame($original, $updated);
        }
        $this->assertDatabaseHas('products', ['id' => $unknownId, 'image_url' => null]);

        $after = DB::table('products')->orderBy('id')->get()->all();
        $migration->up();
        $this->assertEquals($after, DB::table('products')->orderBy('id')->get()->all());
        $migration->down();
        $this->assertEquals($after, DB::table('products')->orderBy('id')->get()->all());
    }
}
