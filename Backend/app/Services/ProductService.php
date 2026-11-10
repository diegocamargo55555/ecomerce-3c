<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public function listCatalog(array $filters): LengthAwarePaginator
    {
        $query = Product::query()->where('is_active', true);

        if (trim($filters['search'] ?? '') !== '') {
            $search = '%'.trim($filters['search']).'%';
            $query->where(fn ($query) => $query
                ->whereLike('name', $search)
                ->orWhereLike('description', $search)
                ->orWhereHas('category', fn ($category) => $category->whereLike('name', $search)));
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        [$column, $direction] = match ($filters['sort'] ?? 'newest') {
            'price-asc' => ['price', 'asc'],
            'price-desc' => ['price', 'desc'],
            'name' => ['name', 'asc'],
            default => ['created_at', 'desc'],
        };

        return $query->orderBy($column, $direction)->orderBy('id', $direction)
            ->paginate($filters['per_page'] ?? 8)->withQueryString();
    }

    public function listAll(): Collection
    {
        return Product::all();
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function findPublicById(int $id): Product
    {
        return Product::with('category')->where('is_active', true)->findOrFail($id);
    }

    public function update(int $id, array $data): Product
    {
        $product = Product::findOrFail($id);
        $product->update($data);

        return $product;
    }

    /**
     * Grava o arquivo no disco "public" (storage/app/public/products), que o
     * Compose mantém em um volume, e aponta image_url para ele. A imagem enviada
     * anteriormente é apagada se nenhum produto a usa; URLs externas e fotos dos seeders não são tocadas.
     */
    public function updateImage(int $id, UploadedFile $file): Product
    {
        $product = Product::findOrFail($id);
        $disk = Storage::disk('public');
        $previousUrl = $product->image_url;
        $previous = $this->uploadedImagePath($previousUrl);

        $path = $file->store('products', 'public');
        abort_if($path === false, 500, 'Não foi possível gravar a imagem.');

        try {
            $product->update(['image_url' => $disk->url($path)]);
        } catch (\Throwable $exception) {
            $disk->delete($path);
            throw $exception;
        }

        if ($previous !== null && $previous !== $path
            && ! Product::withTrashed()->where('image_url', $previousUrl)->exists()) {
            $disk->delete($previous);
        }

        return $product;
    }

    private function uploadedImagePath(?string $url): ?string
    {
        $prefix = Storage::disk('public')->url('products/');

        return $url !== null && str_starts_with($url, $prefix)
            ? 'products/'.basename($url) : null;
    }

    public function delete(int $id): void
    {
        Product::findOrFail($id)->delete();
    }
}
