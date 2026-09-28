<?php

namespace App\Http\Controllers\Customer;

use App\Enums\ProductReleaseStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductRelease;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductReleaseDownloadController extends Controller
{
    public function __invoke(
        Request $request,
        Product $product,
        ProductRelease $productRelease
    ): StreamedResponse {
        if ($productRelease->product_id !== $product->id) {
            abort(404);
        }

        if (
            $productRelease->status !==
            ProductReleaseStatus::Published
        ) {
            abort(404);
        }

        $ownsProduct = $request->user()
            ->productOwnerships()
            ->where('product_id', $product->id)
            ->exists();

        if (! $ownsProduct) {
            abort(404);
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($productRelease->file_path)) {
            abort(404);
        }

        return $disk->download(
            $productRelease->file_path,
            $productRelease->original_name,
            [
                'Content-Type' => 'application/zip',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}