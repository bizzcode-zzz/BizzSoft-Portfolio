<?php

namespace App\Payments\Providers\Paddle;

use Paddle\SDK\Client;
use Paddle\SDK\Environment;
use Paddle\SDK\Options;
use RuntimeException;

final class PaddleClientFactory
{
    public function make(): Client
    {
        $apiKey = trim((string) config('paddle.api_key'));

        if ($apiKey === '') {
            throw new RuntimeException('Paddle API key is not configured.');
        }

        $environment = strtolower(
            trim((string) config('paddle.environment', 'sandbox'))
        );

        return match ($environment) {
            'sandbox' => new Client(
                apiKey: $apiKey,
                options: new Options(Environment::SANDBOX),
            ),

            'production' => new Client(
                apiKey: $apiKey,
            ),

            default => throw new RuntimeException(
                "Unsupported Paddle environment [{$environment}]."
            ),
        };
    }
}
