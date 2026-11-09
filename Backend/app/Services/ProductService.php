<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

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

    public function findById(int $id): Product
    {
        return Product::findOrFail($id);
    }

    public function update(int $id, array $data): Product
    {
        $product = Product::findOrFail($id);
        $product->update($data);

        return $product;
    }

    public function delete(int $id): void
    {
        Product::findOrFail($id)->delete();
    }
}
