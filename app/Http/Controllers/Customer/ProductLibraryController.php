<?php

namespace App\Http\Controllers\Customer;

use App\Enums\ProductReleaseStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductLibraryController extends Controller
{
    public function index(Request $request): Response
    {
        $ownerships = $request->user()
            ->productOwnerships()
            ->with([
                'product.releases' => fn ($query) => $query
                    ->where(
                        'status',
                        ProductReleaseStatus::Published->value
                    )
                    ->latest('released_at')
                    ->latest('id'),
            ])
            ->latest('granted_at')
            ->get();

        return Inertia::render('Customer/Products/Index', [
            'ownedProducts' => $ownerships
                ->map(fn ($ownership) => [
                    'ownership_id' => $ownership->id,
                    'granted_at' => $ownership->granted_at,

                    'product' => [
                        'id' => $ownership->product->id,
                        'name' => $ownership->product->name,
                        'slug' => $ownership->product->slug,
                        'version' => $ownership->product->version,

                        'releases' => $ownership->product->releases
                            ->map(fn ($release) => [
                                'id' => $release->id,
                                'version' => $release->version,
                                'original_name' => $release->original_name,
                                'file_size' => $release->file_size,
                                'released_at' => $release->released_at,
                            ])
                            ->values()
                            ->all(),
                    ],
                ])
                ->values()
                ->all(),
        ]);
    }
}