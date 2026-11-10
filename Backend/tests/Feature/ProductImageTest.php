<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    // Menor PNG válido (1x1): dispensa a extensão GD nos testes.
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $category = Category::create(['name' => 'Miniaturas', 'slug' => 'miniaturas']);
        $this->product = Product::create([
            'name' => 'Marine', 'description' => 'Modelo', 'price' => '10.00',
            'category_id' => $category->id, 'stock' => 1, 'is_active' => true,
            'image_url' => 'https://example.com/externa.jpg',
        ]);
    }

    private function png(string $name = 'foto.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode(self::PNG));
    }

    private function upload(?UploadedFile $file, ?int $id = null)
    {
        return $this->post(
            '/api/admin/products/'.($id ?? $this->product->id).'/image',
            $file ? ['image' => $file] : [],
            ['Accept' => 'application/json'],
        );
    }

    public function test_only_admin_can_upload_a_product_image(): void
    {
        $this->upload($this->png())->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $this->upload($this->png())->assertForbidden();

        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame('https://example.com/externa.jpg', $this->product->fresh()->image_url);
    }

    public function test_admin_upload_stores_the_file_and_updates_the_public_url(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $url = $this->upload($this->png())->assertOk()
            ->assertJsonPath('data.id', $this->product->id)->json('data.image_url');

        $files = Storage::disk('public')->allFiles();
        $this->assertCount(1, $files);
        $this->assertStringStartsWith('products/', $files[0]);
        $this->assertStringEndsWith('.png', $files[0]);
        $this->assertSame(Storage::disk('public')->url($files[0]), $url);
        $this->assertSame($url, $this->product->fresh()->image_url);
        $this->getJson("/api/products/{$this->product->id}")->assertJsonPath('data.image_url', $url);
    }

    public function test_replacing_the_image_deletes_the_previous_upload(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->upload($this->png())->assertOk();
        $first = Storage::disk('public')->allFiles()[0];
        $this->upload($this->png('nova.png'))->assertOk();

        $files = Storage::disk('public')->allFiles();
        $this->assertCount(1, $files);
        $this->assertNotSame($first, $files[0]);
    }

    public function test_upload_validates_the_file_and_the_product(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->upload(null)->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->upload(UploadedFile::fake()->createWithContent('nota.txt', 'texto'))
            ->assertUnprocessable()->assertJsonValidationErrors('image');
        // Conteúdo não-imagem com extensão .png também é recusado em uploads reais
        // (o tipo vem do conteúdo); o arquivo falso dos testes deduz o tipo pelo nome.
        $this->upload(UploadedFile::fake()->create('grande.png', 2049, 'image/png'))
            ->assertUnprocessable()->assertJsonValidationErrors('image');
        $this->upload($this->png(), 999999)->assertNotFound();
        $this->product->delete();
        $this->upload($this->png())->assertNotFound();

        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
