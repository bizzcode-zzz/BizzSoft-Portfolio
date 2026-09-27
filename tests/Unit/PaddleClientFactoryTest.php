<?php

namespace Tests\Unit;

use App\Payments\Providers\Paddle\PaddleClientFactory;
use Paddle\SDK\Client;
use RuntimeException;
use Tests\TestCase;

class PaddleClientFactoryTest extends TestCase
{
    public function test_it_creates_a_sandbox_client(): void
    {
        config([
            'paddle.api_key' => 'test-key',
            'paddle.environment' => 'sandbox',
        ]);

        $client = app(PaddleClientFactory::class)->make();

        $this->assertInstanceOf(Client::class, $client);
    }

    public function test_it_creates_a_production_client(): void
    {
        config([
            'paddle.api_key' => 'test-key',
            'paddle.environment' => 'production',
        ]);

        $client = app(PaddleClientFactory::class)->make();

        $this->assertInstanceOf(Client::class, $client);
    }

    public function test_it_rejects_a_missing_api_key(): void
    {
        config([
            'paddle.api_key' => '',
            'paddle.environment' => 'sandbox',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Paddle API key is not configured.');

        app(PaddleClientFactory::class)->make();
    }

    public function test_it_rejects_an_unknown_environment(): void
    {
        config([
            'paddle.api_key' => 'test-key',
            'paddle.environment' => 'invalid',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Unsupported Paddle environment [invalid].'
        );

        app(PaddleClientFactory::class)->make();
    }
}
