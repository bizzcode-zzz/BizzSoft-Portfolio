<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CustomizationRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\CustomizationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomizationQuoteController extends Controller
{
    public function store(
        Request $request,
        CustomizationRequest $customizationRequest
    ): RedirectResponse {
        $this->authorize('update', $customizationRequest);

        abort_unless(
            $customizationRequest->status === CustomizationRequestStatus::UnderReview,
            422,
            'A quotation can only be sent while the customization request is under review.'
        );

        abort_if(
            $customizationRequest->quote()->exists(),
            422,
            'This customization request already has a quotation.'
        );

        $validated = $request->validate([
            'price' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999.99'],
            'scope' => ['required', 'string', 'max:10000'],
            'estimated_delivery' => ['required', 'date', 'after:today'],
        ]);

        DB::transaction(function () use (
            $customizationRequest,
            $validated
        ): void {
            $lockedRequest = CustomizationRequest::query()
                ->whereKey($customizationRequest->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorize('update', $lockedRequest);

            abort_unless(
                $lockedRequest->status === CustomizationRequestStatus::UnderReview,
                422,
                'A quotation can only be sent while the customization request is under review.'
            );

            abort_if(
                $lockedRequest->quote()->exists(),
                422,
                'This customization request already has a quotation.'
            );

            $lockedRequest->quote()->create([
                'price' => $validated['price'],
                'scope' => $validated['scope'],
                'estimated_delivery' => $validated['estimated_delivery'],
            ]);

            $lockedRequest->update([
                'status' => CustomizationRequestStatus::QuoteSent,
            ]);
        });

        return redirect()
            ->route('admin.customizations.show', $customizationRequest);
    }
}
