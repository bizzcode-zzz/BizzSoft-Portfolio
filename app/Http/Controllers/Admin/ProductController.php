<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        $products = Product::query()
            ->latest()
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'short_description' => $product->short_description,
                'price' => $product->price,
                'status' => $product->status->value,
                'version' => $product->version,
                'thumbnail_url' => $this->thumbnailUrl(
                    $product->thumbnail_path
                ),
                'demo_url' => $product->demo_url,
                'created_at' => $product->created_at,
            ]);

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Products/Create', [
            'statuses' => $this->statuses(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProduct($request);

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        $slug = $this->uniqueSlug($slug);

        $thumbnailPath = null;

        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request
                ->file('thumbnail')
                ->store('products/thumbnails', 'public');
        }

        Product::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'status' => $validated['status'],
            'version' => $validated['version'] ?? null,
            'thumbnail_path' => $thumbnailPath,
            'demo_url' => $validated['demo_url'] ?? null,
            'built_with' => $validated['built_with'] ?? null,
            'server_requirement' => $validated['server_requirement'] ?? null,
            'database_system' => $validated['database_system'] ?? null,
            'browser_support' => $validated['browser_support'] ?? null,
            'included_items' => $this->normalizeIncludedItems(
                $validated['included_items'] ?? []
            ),
        ]);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function show(Product $product): Response
    {
        return Inertia::render('Admin/Products/Show', [
            'product' => $this->productData($product),
        ]);
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Admin/Products/Edit', [
            'product' => $this->productData($product),
            'statuses' => $this->statuses(),
        ]);
    }

    public function update(
        Request $request,
        Product $product
    ): RedirectResponse {
        $validated = $this->validateProduct($request, $product);

        $slug = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        $slug = $this->uniqueSlug($slug, $product);

        $thumbnailPath = $product->thumbnail_path;

        if ($request->hasFile('thumbnail')) {
            if ($thumbnailPath) {
                Storage::disk('public')->delete($thumbnailPath);
            }

            $thumbnailPath = $request
                ->file('thumbnail')
                ->store('products/thumbnails', 'public');
        }

        $data = [
            'name' => $validated['name'],
            'slug' => $slug,
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'status' => $validated['status'],
            'version' => $validated['version'] ?? null,
            'thumbnail_path' => $thumbnailPath,
            'demo_url' => $validated['demo_url'] ?? null,
        ];

        if (array_key_exists('built_with', $validated)) {
            $data['built_with'] = $validated['built_with'];
        }

        if (array_key_exists('server_requirement', $validated)) {
            $data['server_requirement'] = $validated['server_requirement'];
        }

        if (array_key_exists('database_system', $validated)) {
            $data['database_system'] = $validated['database_system'];
        }

        if (array_key_exists('browser_support', $validated)) {
            $data['browser_support'] = $validated['browser_support'];
        }

        if (array_key_exists('included_items', $validated)) {
            $data['included_items'] = $this->normalizeIncludedItems(
                $validated['included_items'] ?? []
            );
        }

        $product->update($data);

        return redirect()
            ->route('admin.products.show', $product)
            ->with('success', 'Product updated successfully.');
    }

    private function validateProduct(
        Request $request,
        ?Product $product = null
    ): array {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($product?->id),
            ],

            'short_description' => [
                'nullable',
                'string',
                'max:500',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'status' => [
                'required',
                Rule::enum(ProductStatus::class),
            ],

            'version' => [
                'nullable',
                'string',
                'max:100',
            ],

            'demo_url' => [
                'nullable',
                'url',
                'max:2048',
            ],

            'thumbnail' => [
                'nullable',
                'image',
                'max:5120',
            ],

            'built_with' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'server_requirement' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'database_system' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'browser_support' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],

            'included_items' => [
                'sometimes',
                'nullable',
                'array',
                'max:20',
            ],

            'included_items.*' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);
    }

    private function productData(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'short_description' => $product->short_description,
            'description' => $product->description,
            'price' => $product->price,
            'status' => $product->status->value,
            'version' => $product->version,
            'thumbnail_url' => $this->thumbnailUrl(
                $product->thumbnail_path
            ),
            'demo_url' => $product->demo_url,
            'built_with' => $product->built_with,
            'server_requirement' => $product->server_requirement,
            'database_system' => $product->database_system,
            'browser_support' => $product->browser_support,
            'included_items' => $product->included_items ?? [],
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
        ];
    }

    private function thumbnailUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->url($path);
    }

    private function statuses(): array
    {
        return collect(ProductStatus::cases())
            ->map(fn (ProductStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->values()
            ->all();
    }

    private function normalizeIncludedItems(array $items): array
    {
        return collect($items)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    private function uniqueSlug(
        string $slug,
        ?Product $ignoreProduct = null
    ): string {
        $baseSlug = $slug !== '' ? $slug : 'product';
        $candidate = $baseSlug;
        $counter = 2;

        while (
            Product::query()
                ->where('slug', $candidate)
                ->when(
                    $ignoreProduct,
                    fn ($query) => $query->whereKeyNot(
                        $ignoreProduct->id
                    )
                )
                ->exists()
        ) {
            $candidate = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }
}