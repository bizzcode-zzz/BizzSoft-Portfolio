<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        $products = Product::query()
            ->where('status', ProductStatus::Active)
            ->latest()
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'short_description' => $product->short_description,
                'price' => $product->price,
                'version' => $product->version,
                'thumbnail_url' => $this->thumbnailUrl(
                    $product->thumbnail_path
                ),
                'demo_url' => $product->demo_url,
            ]);

        return Inertia::render('Products/Index', [
            'products' => $products,
        ]);
    }

    public function show(string $product): Response
    {
        $product = Product::query()
            ->where('slug', $product)
            ->where('status', ProductStatus::Active)
            ->firstOrFail();

        return Inertia::render('Products/Show', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'price' => $product->price,
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
            ],
        ]);
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
}