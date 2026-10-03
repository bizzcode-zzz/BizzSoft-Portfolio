<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentWebhookEvent;
use Inertia\Inertia;
use Inertia\Response;

class PaymentWebhookEventController extends Controller
{
    public function index(): Response
    {
        $events = PaymentWebhookEvent::query()
            ->latest('id')
            ->paginate(25)
            ->through(fn (PaymentWebhookEvent $event) => [
                'id' => $event->id,
                'provider' => $event->provider,
                'event_id' => $event->event_id,
                'event_type' => $event->event_type,
                'provider_payment_id' => $event->provider_payment_id,
                'occurred_at' => $event->occurred_at,
                'processed_at' => $event->processed_at,
                'failed_at' => $event->failed_at,
                'failure_message' => $event->failure_message,
                'created_at' => $event->created_at,

                'status' => $event->failed_at
                    ? 'failed'
                    : ($event->processed_at ? 'processed' : 'pending'),
            ]);

        return Inertia::render('Admin/PaymentWebhookEvents/Index', [
            'events' => $events,
        ]);
    }

    public function show(
        PaymentWebhookEvent $paymentWebhookEvent
    ): Response {
        return Inertia::render('Admin/PaymentWebhookEvents/Show', [
            'event' => [
                'id' => $paymentWebhookEvent->id,
                'provider' => $paymentWebhookEvent->provider,
                'event_id' => $paymentWebhookEvent->event_id,
                'event_type' => $paymentWebhookEvent->event_type,
                'provider_payment_id' => $paymentWebhookEvent->provider_payment_id,
                'occurred_at' => $paymentWebhookEvent->occurred_at,
                'processed_at' => $paymentWebhookEvent->processed_at,
                'failed_at' => $paymentWebhookEvent->failed_at,
                'failure_message' => $paymentWebhookEvent->failure_message,
                'metadata' => $paymentWebhookEvent->metadata,
                'created_at' => $paymentWebhookEvent->created_at,

                'status' => $paymentWebhookEvent->failed_at
                    ? 'failed'
                    : ($paymentWebhookEvent->processed_at ? 'processed' : 'pending'),
            ],
        ]);
    }
}
