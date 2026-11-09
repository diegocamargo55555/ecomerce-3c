<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Services\CategoryService;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    public function test_guests_can_list_products(): void
    {
        $product = (new Product)->forceFill([
            'id' => 1,
            'category_id' => 2,
            'name' => 'Produto público',
            'price' => '19.90',
            'stock' => 3,
            'is_active' => true,
        ]);
        $this->mock(ProductService::class)
            ->shouldReceive('listAll')->once()
            ->andReturn(new Collection([$product]));

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Produto público')
            ->assertJsonPath('data.0.price', '19.90');
    }

    public function test_guests_can_list_categories(): void
    {
        $category = (new Category)->forceFill([
            'id' => 2,
            'name' => 'Categoria pública',
            'slug' => 'categoria-publica',
        ]);
        $this->mock(CategoryService::class)
            ->shouldReceive('listAll')->once()
            ->andReturn(new Collection([$category]));

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Categoria pública');
    }

    public function test_guests_cannot_manage_catalog_or_read_private_data(): void
    {
        foreach (['products', 'categories'] as $resource) {
            $this->postJson("/api/{$resource}")->assertUnauthorized();
            $this->putJson("/api/{$resource}/1")->assertUnauthorized();
            $this->patchJson("/api/{$resource}/1")->assertUnauthorized();
            $this->deleteJson("/api/{$resource}/1")->assertUnauthorized();
        }

        foreach (['me', 'users', 'addresses', 'orders', 'order-items', 'carts', 'cart-items', 'payments'] as $resource) {
            $this->getJson("/api/{$resource}")->assertUnauthorized();
        }
    }
}
