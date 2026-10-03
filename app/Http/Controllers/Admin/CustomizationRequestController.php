<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CustomizationRequestStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Payments\PaymentEntitlements;
use App\Models\CustomizationRequest;
use Inertia\Inertia;
use Inertia\Response;

class CustomizationRequestController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', CustomizationRequest::class);

        $customizationRequests = CustomizationRequest::query()
            ->with('user:id,name,email')
            ->withCount([
                'messages as unread_messages_count' => function ($query) {
                    $query
                        ->whereNull('read_at')
                        ->whereHas('user.roles', function ($query) {
                            $query->where('name', 'customer');
                        });
                },
            ])
            ->latest('created_at')
            ->latest('id')
            ->paginate(25);

        return Inertia::render('Admin/Customizations/Index', [
            'customizationRequests' => $customizationRequests,
        ]);
    }

    public function show(CustomizationRequest $customizationRequest): Response
    {
        $this->authorize('view', $customizationRequest);

        $customizationRequest->load(
            'user:id,name,email'
        );

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
                    $query->where('name', 'customer');
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

        $quotePayment = $this->quotePaymentSummary(
            $customizationRequest
        );

        $paymentRecovery = $this->paymentRecovery(
            $customizationRequest
        );

        return Inertia::render('Admin/Customizations/Show', [
            'customizationRequest' => $customizationRequest,
            'messageHistory' => $messageHistory,
            'secureAccesses' => $secureAccesses,
            'quotePayment' => $quotePayment,
            'paymentRecovery' => $paymentRecovery,
        ]);
    }

    private function quotePaymentSummary(
        CustomizationRequest $customizationRequest
    ): ?array {
        $quote = $customizationRequest
            ->quote()
            ->first();

        if ($quote === null) {
            return null;
        }

        $payments = $quote
            ->payments()
            ->where(
                'status',
                PaymentStatus::Verified->value
            )
            ->where(
                'user_id',
                $customizationRequest->user_id
            )
            ->where(
                'currency',
                'USD'
            )
            ->orderBy('id')
            ->get();

        $verifiedMinor = $payments->sum(
            fn ($payment) =>
                $this->toMinorUnits(
                    (string) $payment->amount
                )
        );

        $entitlements = app(
            PaymentEntitlements::class
        );

        $usableMinor = $payments
            ->reject(
                fn ($payment) =>
                    $entitlements->paymentIsHeld(
                        $payment->id
                    )
            )
            ->sum(
                fn ($payment) =>
                    $this->toMinorUnits(
                        (string) $payment->amount
                    )
            );

        $quoteMinor = $this->toMinorUnits(
            (string) $quote->price
        );

        return [
            'quote_id' => $quote->id,
            'price' => number_format(
                $quoteMinor / 100,
                2,
                '.',
                ''
            ),
            'verified_amount' => number_format(
                $verifiedMinor / 100,
                2,
                '.',
                ''
            ),
            'usable_verified_amount' => number_format(
                $usableMinor / 100,
                2,
                '.',
                ''
            ),
            'remaining_amount' => number_format(
                max($quoteMinor - $usableMinor, 0) / 100,
                2,
                '.',
                ''
            ),
            'has_active_hold' =>
                $verifiedMinor > $usableMinor,
            'fully_paid_and_unheld' =>
                $entitlements
                    ->customizationQuoteIsFullyPaidAndUnheld(
                        $quote
                    ),
        ];
    }
    private function paymentRecovery(
        CustomizationRequest $customizationRequest
    ): array {
        $quote = $customizationRequest
            ->quote()
            ->first();

        if ($quote === null) {
            return [
                'needs_review' => false,
                'verified_amount' => '0.00',
                'can_recover' => false,
            ];
        }

        $payments = $quote
            ->payments()
            ->where(
                'status',
                PaymentStatus::Verified->value
            )
            ->where(
                'user_id',
                $customizationRequest->user_id
            )
            ->where(
                'currency',
                'USD'
            )
            ->orderBy('id')
            ->get();

        $verifiedMinor = $payments->sum(
            fn ($payment) =>
                $this->toMinorUnits(
                    (string) $payment->amount
                )
        );

        $entitlements = app(
            PaymentEntitlements::class
        );

        $usableMinor = $payments
            ->reject(
                fn ($payment) =>
                    $entitlements->paymentIsHeld(
                        $payment->id
                    )
            )
            ->sum(
                fn ($payment) =>
                    $this->toMinorUnits(
                        (string) $payment->amount
                    )
            );

        $quoteMinor = $this->toMinorUnits(
            (string) $quote->price
        );

        $needsReview =
            $customizationRequest->status ===
                CustomizationRequestStatus::Cancelled
            && $verifiedMinor > 0;

        return [
            'needs_review' => $needsReview,
            'verified_amount' => number_format(
                $verifiedMinor / 100,
                2,
                '.',
                ''
            ),
            'can_recover' =>
                $needsReview
                && $usableMinor >= $quoteMinor,
        ];
    }

    private function toMinorUnits(
        string $amount
    ): int {
        $amount = trim($amount);

        [$whole, $fraction] = array_pad(
            explode('.', $amount, 2),
            2,
            ''
        );

        $fraction = str_pad(
            substr($fraction, 0, 2),
            2,
            '0'
        );

        return ((int) $whole * 100)
            + (int) $fraction;
    }
}
