<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaddleWebhookSignatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'paddle.webhook_secret' => 'test-webhook-secret',
        ]);
    }

    #[DataProvider('malformedSignatures')]
    public function test_malformed_signatures_are_rejected_without_webhook_event_rows(
        ?string $signature
    ): void {
        if ($signature !== null) {
            $this->withHeader(
                'Paddle-Signature',
                $signature
            );
        }

        $this->postJson('/webhooks/paddle', [])
            ->assertStatus(400)
            ->assertJson([
                'message' => 'Invalid Paddle webhook signature.',
            ]);

        $this->assertDatabaseCount(
            'payment_webhook_events',
            0
        );
    }

    public function test_repeated_malformed_signatures_do_not_grow_webhook_event_table(): void
    {
        for ($attempt = 1; $attempt <= 20; $attempt++) {
            $this
                ->withHeader(
                    'Paddle-Signature',
                    "malformed-signature-{$attempt}"
                )
                ->postJson('/webhooks/paddle', [
                    'junk' => $attempt,
                ])
                ->assertStatus(400);
        }

        $this->assertDatabaseCount(
            'payment_webhook_events',
            0
        );
    }

    public static function malformedSignatures(): array
    {
        return [
            'missing header' => [null],
            'plain garbage' => ['not-a-paddle-signature'],
            'timestamp only' => ['ts=1700000000'],
            'hash only' => ['h1=deadbeef'],
            'invalid timestamp' => ['ts=not-a-number;h1=deadbeef'],
            'empty values' => ['ts=;h1='],
            'separator garbage' => [';;;;'],
        ];
    }
}
