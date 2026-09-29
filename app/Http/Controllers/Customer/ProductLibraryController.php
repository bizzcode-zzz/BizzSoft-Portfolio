<?php

namespace App\Http\Controllers\Customer;

use App\Enums\ProductReleaseStatus;
use App\Http\Controllers\Controller;
use App\Models\ProductRelease;
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
                'startingRelease:id,released_at',
                'licenses',
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
                ->map(function ($ownership) {
                    $eligibleReleases = $ownership
                        ->product
                        ->releases
                        ->filter(
                            fn (ProductRelease $release) =>
                                $this->isReleaseEntitled(
                                    $release,
                                    $ownership->startingRelease
                                )
                        );

                    $latestEligibleReleaseId = $eligibleReleases->first()?->id;

                    return [
                        'ownership_id' => $ownership->id,
                        'granted_at' => $ownership->granted_at,

                        'licenses' => $ownership->licenses
                            ->sortBy('id')
                            ->values()
                            ->map(fn ($license) => [
                                'id' => $license->id,
                                'license_key' => $license->license_key,
                                'status' => $license->status,
                                'production_domain' => $license->production_domain,
                                'activated_at' => $license->activated_at,
                            ])
                            ->all(),

                        'product' => [
                            'id' => $ownership->product->id,
                            'name' => $ownership->product->name,
                            'slug' => $ownership->product->slug,
                            'version' => $ownership->product->version,

                            'releases' => $eligibleReleases
                                ->map(fn ($release) => [
                                    'id' => $release->id,
                                    'version' => $release->version,
                                    'is_latest' => $release->id === $latestEligibleReleaseId,
                                    'original_name' => $release->original_name,
                                    'file_size' => $release->file_size,
                                    'release_notes' => $release->release_notes,
                                    'upgrade_notes' => $release->upgrade_notes,
                                    'released_at' => $release->released_at,
                                    'package_replaced_at' => $release->package_replaced_at,
                                ])
                                ->values()
                                ->all(),
                        ],
                    ];
                })
                ->values()
                ->all(),
        ]);
    }

    private function isReleaseEntitled(
        ProductRelease $release,
        ?ProductRelease $startingRelease
    ): bool {
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
