<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Miniaturas', 'slug' => 'miniaturas']);
        $other = Category::create(['name' => 'Tintas', 'slug' => 'tintas']);
        foreach ([
            ['name' => 'Marine caro', 'price' => '100.00', 'category_id' => $category->id, 'stock' => 3, 'is_active' => true, 'created_at' => '2026-01-01'],
            ['name' => 'Marine barato', 'price' => '9.90', 'category_id' => $category->id, 'stock' => 0, 'is_active' => true, 'created_at' => '2026-01-03'],
            ['name' => 'Marine tinta', 'price' => '20.00', 'category_id' => $other->id, 'stock' => 5, 'is_active' => true, 'created_at' => '2026-01-02'],
            ['name' => 'Marine inativo', 'price' => '1.00', 'category_id' => $category->id, 'stock' => 5, 'is_active' => false, 'created_at' => '2026-01-04'],
        ] as $product) {
            Product::unguarded(fn () => Product::create($product + ['description' => 'Modelo para pintar']));
        }
    }

    public function test_public_catalog_filters_inactive_and_deleted_products_and_paginates(): void
    {
        $this->getJson('/api/products?per_page=2')
            ->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Marine barato')
            ->assertJsonPath('data.0.stock', 0)
            ->assertJsonPath('data.1.name', 'Marine tinta')
            ->assertJsonPath('meta.total', 3)->assertJsonPath('meta.last_page', 2);

        $this->getJson('/api/products?per_page=2&page=2')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Marine caro')->assertJsonPath('meta.current_page', 2);
    }

    public function test_search_category_and_price_order_are_applied_together(): void
    {
        $category = Category::where('slug', 'miniaturas')->value('id');
        $this->getJson("/api/products?search=Marine&category_id={$category}&sort=price-asc")
            ->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Marine barato')->assertJsonPath('data.1.name', 'Marine caro');

        $this->getJson('/api/products?sort=price-desc')->assertJsonPath('data.0.name', 'Marine caro');
        $this->getJson('/api/products?sort=name')->assertJsonPath('data.0.name', 'Marine barato');
        $this->getJson('/api/products?search=pintar')->assertJsonPath('meta.total', 3);
        $this->getJson('/api/products?search=Tintas')->assertJsonPath('data.0.name', 'Marine tinta');
        $this->getJson('/api/products?search=inexistente')->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('meta.total', 0);
        $this->getJson('/api/products?search=0')->assertJsonPath('meta.total', 0);
    }

    public function test_catalog_validates_parameters(): void
    {
        $this->getJson('/api/products?sort=invalid&page=0&per_page=1000&category_id=-1')
            ->assertUnprocessable()->assertJsonValidationErrors(['sort', 'page', 'per_page', 'category_id']);
    }

    public function test_product_details_are_public_and_include_the_category(): void
    {
        $product = Product::where('name', 'Marine caro')->firstOrFail();
        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Marine caro')
            ->assertJsonPath('data.price', '100.00')
            ->assertJsonPath('data.stock', 3)
            ->assertJsonPath('data.category.name', 'Miniaturas');

        $outOfStock = Product::where('stock', 0)->firstOrFail();
        $this->getJson("/api/products/{$outOfStock->id}")->assertOk()->assertJsonPath('data.stock', 0);
    }

    public function test_inactive_deleted_and_missing_product_details_return_404_even_for_admin(): void
    {
        $ids = Product::where('is_active', false)->pluck('id');
        foreach ([...$ids, 999999, 'invalid'] as $id) {
            $this->getJson("/api/products/{$id}")->assertNotFound();
        }
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach ($ids as $id) {
            $this->getJson("/api/products/{$id}")->assertNotFound();
        }
    }

    public function test_category_with_linked_products_cannot_be_deleted(): void
    {
        $withProducts = Category::where('slug', 'tintas')->firstOrFail();
        $empty = Category::create(['name' => 'Vazia', 'slug' => 'vazia']);

        $this->deleteJson("/api/categories/{$empty->id}")->assertUnauthorized();
        $this->actingAs(User::factory()->create())
            ->deleteJson("/api/categories/{$empty->id}")->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->deleteJson("/api/categories/{$withProducts->id}")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Não é possível excluir a categoria Tintas: há 1 produto(s) vinculado(s). Mova ou exclua os produtos antes.');
        $this->assertDatabaseHas('categories', ['id' => $withProducts->id]);
        $this->assertDatabaseHas('products', ['category_id' => $withProducts->id]);

        $this->deleteJson("/api/categories/{$empty->id}")->assertNoContent();
        $this->assertDatabaseMissing('categories', ['id' => $empty->id]);
        $this->deleteJson('/api/categories/999999')->assertNotFound();
    }

    public function test_only_admin_can_list_all_products_including_inactive_ones(): void
    {
        $this->getJson('/api/admin/products')->assertUnauthorized();
        $this->actingAs(User::factory()->create())->getJson('/api/admin/products')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->getJson('/api/admin/products')->assertOk()->assertJsonCount(4, 'data');
        $this->getJson('/api/products')->assertJsonPath('meta.total', 3);
    }

    public function test_category_list_shows_the_photo_of_its_first_active_product(): void
    {
        // Inativo não empresta foto; categoria sem foto fica nula.
        Product::where('name', 'Marine inativo')->update(['image_url' => '/images/products/inativo.jpg']);
        Product::where('name', 'Marine barato')->update(['image_url' => '/images/products/barato.jpg']);

        $this->getJson('/api/categories')->assertOk()
            ->assertJsonPath('data.0.image_url', '/images/products/barato.jpg')
            ->assertJsonPath('data.1.image_url', null);

        // Foto própria da categoria vence a do produto.
        $id = $this->getJson('/api/categories')->json('data.0.id');
        \App\Models\Category::findOrFail($id)->update(['image_url' => '/images/categories/propria.jpg']);
        $this->getJson('/api/categories')->assertJsonPath('data.0.image_url', '/images/categories/propria.jpg');
    }
}
