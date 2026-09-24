<?php

namespace App\Http\Controllers;

use App\Enums\CustomizationRequestStatus;
use App\Models\CustomizationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class CustomizationQuoteDecisionController extends Controller
{
    public function accept(
        CustomizationRequest $customizationRequest
    ): RedirectResponse {
        $this->authorize('update', $customizationRequest);

        $this->ensureQuoteCanBeDecided($customizationRequest);

        DB::transaction(function () use ($customizationRequest): void {
            $customizationRequest->update([
                'status' => CustomizationRequestStatus::Accepted,
            ]);
        });

        return redirect()
            ->route('customizations.show', $customizationRequest);
    }

    public function decline(
        CustomizationRequest $customizationRequest
    ): RedirectResponse {
        $this->authorize('update', $customizationRequest);

        $this->ensureQuoteCanBeDecided($customizationRequest);

        DB::transaction(function () use ($customizationRequest): void {
            $customizationRequest->update([
                'status' => CustomizationRequestStatus::QuoteDeclined,
            ]);
        });

        return redirect()
            ->route('customizations.show', $customizationRequest);
    }

    private function ensureQuoteCanBeDecided(
        CustomizationRequest $customizationRequest
    ): void {
        abort_unless(
            $customizationRequest->status === CustomizationRequestStatus::QuoteSent,
            422,
            'This quotation can only be decided while it is awaiting your decision.'
        );

        abort_unless(
            $customizationRequest->quote()->exists(),
            422,
            'This customization request does not have a quotation.'
        );
    }
}