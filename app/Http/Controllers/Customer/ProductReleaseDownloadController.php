<?php

namespace App\Http\Controllers\Customer;

use App\Enums\ProductReleaseStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductOwnership;
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

        $ownership = $request->user()
            ->productOwnerships()
            ->with('startingRelease')
            ->where('product_id', $product->id)
            ->first();

        if ($ownership === null) {
            abort(404);
        }

        if (! $this->isReleaseEntitled($productRelease, $ownership)) {
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

    private function isReleaseEntitled(
        ProductRelease $release,
        ProductOwnership $ownership
    ): bool {
        $startingRelease = $ownership->startingRelease;

        if ($startingRelease === null) {
            return true;
        }

        if (
            $release->released_at === null ||
            $startingRelease->released_at === null
        ) {
            return $release->id >= $startingRelease->id;
        }

        if ($release->released_at->gt($startingRelease->released_at)) {
            return true;
        }

        if ($release->released_at->lt($startingRelease->released_at)) {
            return false;
        }

        return $release->id >= $startingRelease->id;
    }
}
