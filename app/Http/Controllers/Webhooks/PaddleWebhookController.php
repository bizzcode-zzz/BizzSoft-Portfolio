<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Payments\Providers\Paddle\PaddleWebhookProcessor;
use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Paddle\SDK\Entities\Event;
use Paddle\SDK\Notifications\Secret;
use Paddle\SDK\Notifications\Verifier;
use RuntimeException;

final class PaddleWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        PaddleWebhookProcessor $processor,
    ): JsonResponse {
        $webhookSecret = trim(
            (string) config('paddle.webhook_secret')
        );

        if ($webhookSecret === '') {
            throw new RuntimeException(
                'Paddle webhook secret is not configured.'
            );
        }

        $psrRequest = new ServerRequest(
            $request->method(),
            $request->fullUrl(),
            $request->headers->all(),
            $request->getContent(),
        );

        $verified = (new Verifier())->verify(
            $psrRequest,
            new Secret($webhookSecret)
        );

        if (! $verified) {
            return response()->json([
                'message' => 'Invalid Paddle webhook signature.',
            ], 400);
        }

        $event = Event::fromRequest(
            $psrRequest
        );

        $processed = $processor->process(
            $event
        );

        return response()->json([
            'received' => true,
            'processed' => $processed,
        ]);
    }
}
