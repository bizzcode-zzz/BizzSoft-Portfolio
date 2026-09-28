<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductReleaseStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductRelease;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductReleaseController extends Controller
{
    public function store(
        Request $request,
        Product $product
    ): RedirectResponse {
        $validated = $request->validate([
            'version' => [
                'required',
                'string',
                'max:100',
                Rule::unique('product_releases', 'version')
                    ->where(
                        fn ($query) => $query
                            ->where('product_id', $product->id)
                    ),
            ],
            'release_notes' => [
                'nullable',
                'string',
                'max:20000',
            ],
            'upgrade_notes' => [
                'nullable',
                'string',
                'max:20000',
            ],
            'release_file' => [
                'required',
                'file',
                'mimes:zip',
                'max:524288',
            ],
        ]);

        $file = $request->file('release_file');

        $sha256 = hash_file(
            'sha256',
            $file->getRealPath()
        );

        if ($sha256 === false) {
            throw ValidationException::withMessages([
                'release_file' => 'The uploaded release could not be verified.',
            ]);
        }

        $storedPath = $file->storeAs(
            'product-releases/'.$product->id,
            Str::uuid()->toString().'.zip',
            'local'
        );

        if ($storedPath === false) {
            throw ValidationException::withMessages([
                'release_file' => 'The uploaded release could not be stored.',
            ]);
        }

        try {
            ProductRelease::create([
                'product_id' => $product->id,
                'created_by' => $request->user()->id,
                'version' => $validated['version'],
                'file_path' => $storedPath,
                'original_name' => $file->getClientOriginalName(),
                'file_size' => (int) $file->getSize(),
                'sha256' => $sha256,
                'release_notes' => $validated['release_notes'] ?? null,
                'upgrade_notes' => $validated['upgrade_notes'] ?? null,
                'status' => ProductReleaseStatus::Draft,
                'released_at' => null,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPath);

            throw $exception;
        }

        return redirect()
            ->route('admin.products.show', $product)
            ->with(
                'success',
                'Product release uploaded successfully as a draft.'
            );
    }

    public function update(
        Request $request,
        Product $product,
        ProductRelease $productRelease
    ): RedirectResponse {
        if ($productRelease->product_id !== $product->id) {
            abort(404);
        }

        if ($productRelease->status === ProductReleaseStatus::Retired) {
            throw ValidationException::withMessages([
                'release' => 'Retired releases cannot be edited.',
            ]);
        }

        $validated = $request->validate([
            'version' => [
                'required',
                'string',
                'max:100',
                Rule::unique('product_releases', 'version')
                    ->where(
                        fn ($query) => $query
                            ->where('product_id', $product->id)
                    )
                    ->ignore($productRelease->id),
            ],
            'release_notes' => [
                'nullable',
                'string',
                'max:20000',
            ],
            'upgrade_notes' => [
                'nullable',
                'string',
                'max:20000',
            ],
            'release_file' => [
                'nullable',
                'file',
                'mimes:zip',
                'max:524288',
            ],
        ]);

        $updates = [
            'version' => $validated['version'],
            'release_notes' => $validated['release_notes'] ?? null,
            'upgrade_notes' => $validated['upgrade_notes'] ?? null,
        ];

        $newStoredPath = null;
        $oldStoredPath = $productRelease->file_path;

        if ($request->hasFile('release_file')) {
            $file = $request->file('release_file');

            $sha256 = hash_file(
                'sha256',
                $file->getRealPath()
            );

            if ($sha256 === false) {
                throw ValidationException::withMessages([
                    'release_file' => 'The uploaded release could not be verified.',
                ]);
            }

            $newStoredPath = $file->storeAs(
                'product-releases/'.$product->id,
                Str::uuid()->toString().'.zip',
                'local'
            );

            if ($newStoredPath === false) {
                throw ValidationException::withMessages([
                    'release_file' => 'The uploaded release could not be stored.',
                ]);
            }

            $updates = [
                ...$updates,
                'file_path' => $newStoredPath,
                'original_name' => $file->getClientOriginalName(),
                'file_size' => (int) $file->getSize(),
                'sha256' => $sha256,
            ];

            if (
                $productRelease->status ===
                ProductReleaseStatus::Published
            ) {
                $updates['package_replaced_at'] = now();
            }
        }

        try {
            $productRelease->update($updates);
        } catch (Throwable $exception) {
            if ($newStoredPath !== null) {
                Storage::disk('local')->delete($newStoredPath);
            }

            throw $exception;
        }

        if (
            $newStoredPath !== null &&
            $oldStoredPath !== $newStoredPath
        ) {
            Storage::disk('local')->delete($oldStoredPath);
        }

        return redirect()
            ->route('admin.products.show', $product)
            ->with(
                'success',
                'Product release updated successfully.'
            );
    }

    public function publish(
        Product $product,
        ProductRelease $productRelease
    ): RedirectResponse {
        if ($productRelease->product_id !== $product->id) {
            abort(404);
        }

        if ($productRelease->status !== ProductReleaseStatus::Draft) {
            throw ValidationException::withMessages([
                'release' => 'Only draft releases can be published.',
            ]);
        }

        $productRelease->update([
            'status' => ProductReleaseStatus::Published,
            'released_at' => now(),
        ]);

        return redirect()
            ->route('admin.products.show', $product)
            ->with(
                'success',
                'Product release published successfully.'
            );
    }
}