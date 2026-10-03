<?php

namespace App\Http\Controllers;

use App\Enums\CustomizationRequestStatus;
use App\Enums\PaymentStatus;
use App\Models\CustomizationRequest;
use App\Payments\PaymentEntitlements;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomizationRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CustomizationRequest::class);

        $customizationRequests = $request->user()
            ->customizationRequests()
            ->withCount([
                'messages as unread_messages_count' => function ($query) {
                    $query
                        ->whereNull('read_at')
                        ->whereHas('user.roles', function ($query) {
                            $query->where('name', 'admin');
                        });
                },
            ])
            ->latest('created_at')
            ->latest('id')
            ->paginate(25);

        return Inertia::render('Customer/Customizations/Index', [
            'customizationRequests' => $customizationRequests,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', CustomizationRequest::class);

        return Inertia::render('Customer/Customizations/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CustomizationRequest::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
        ]);

        $customizationRequest = $request->user()
            ->customizationRequests()
            ->create([
                ...$validated,
                'status' => CustomizationRequestStatus::Submitted,
            ]);

        return redirect()->route(
            'customizations.show',
            $customizationRequest,
        );
    }

    public function show(
        CustomizationRequest $customizationRequest,
        PaymentEntitlements $entitlements
    ): Response
    {
        $this->authorize('view', $customizationRequest);

        $customizationRequest->load('quote');

        $messageHistory = $customizationRequest
            ->messages()
            ->with('user:id,name,email')
            ->latest('created_at')
            ->latest('id')
            ->simplePaginate(
                50,
                ['*'],
                'messages_page'
            );

        $readAt = now();
        $visibleMessageIds = $messageHistory
            ->getCollection()
            ->modelKeys();

        if ($visibleMessageIds !== []) {
            $readMessageIds = $customizationRequest
                ->messages()
                ->whereKey($visibleMessageIds)
                ->whereNull('read_at')
                ->whereHas('user.roles', function ($query) {
                    $query->where('name', 'admin');
                })
                ->pluck('id');

            if ($readMessageIds->isNotEmpty()) {
                $customizationRequest
                    ->messages()
                    ->whereKey($readMessageIds->all())
                    ->update([
                        'read_at' => $readAt,
                    ]);

                $readMessageLookup = $readMessageIds->flip();

                $messageHistory
                    ->getCollection()
                    ->each(
                        function ($message) use (
                            $readMessageLookup,
                            $readAt
                        ): void {
                            if ($readMessageLookup->has($message->id)) {
                                $message->read_at = $readAt;
                            }
                        }
                    );
            }
        }

        $messageHistory->setCollection(
            $messageHistory
                ->getCollection()
                ->reverse()
                ->values()
        );

        $secureAccesses = $customizationRequest
            ->secureAccesses()
            ->select([
                'id',
                'customization_request_id',
                'created_by',
                'direction',
                'type',
                'label',
                'status',
                'submitted_at',
                'viewed_at',
                'closed_at',
                'created_at',
                'updated_at',
            ])
            ->oldest('created_at')
            ->oldest('id')
            ->get();

        $paymentSummary = null;

        if ($customizationRequest->quote !== null) {
            $verifiedPayments = $customizationRequest
                ->quote
                ->payments()
                ->where(
                    'status',
                    PaymentStatus::Verified->value
                )
                ->get([
                    'id',
                    'amount',
                ]);

            $verifiedAmount = (float) $verifiedPayments
                ->sum('amount');

            $remainingAmount = max(
                (float) $customizationRequest->quote->price
                    - $verifiedAmount,
                0
            );

            $hasActiveHold = $verifiedPayments->contains(
                fn ($payment): bool => $entitlements->paymentIsHeld(
                    (int) $payment->id
                )
            );

            $fullyPaidAndUnheld =
                $entitlements->customizationQuoteIsFullyPaidAndUnheld(
                    $customizationRequest->quote
                );

            $paymentSummary = [
                'verified_amount' => number_format(
                    $verifiedAmount,
                    2,
                    '.',
                    ''
                ),
                'remaining_amount' => number_format(
                    $remainingAmount,
                    2,
                    '.',
                    ''
                ),
                'fully_paid' => $remainingAmount <= 0,
                'has_active_hold' => $hasActiveHold,
                'fully_paid_and_unheld' => $fullyPaidAndUnheld,
            ];
        }

        return Inertia::render('Customer/Customizations/Show', [
            'customizationRequest' => $customizationRequest,
            'messageHistory' => $messageHistory,
            'secureAccesses' => $secureAccesses,
            'paymentSummary' => $paymentSummary,
        ]);
    }
}
