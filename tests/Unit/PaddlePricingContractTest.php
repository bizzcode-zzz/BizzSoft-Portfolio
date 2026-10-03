<?php

namespace Tests\Unit;

use App\Models\CustomizationQuote;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Providers\Paddle\PaddleClientFactory;
use App\Payments\Providers\Paddle\PaddleGateway;
use App\Payments\Providers\Paddle\PaddleWebhookProcessor;
use GuzzleHttp\Psr7\Response;
use Http\Client\HttpAsyncClient;
use Http\Discovery\HttpAsyncClientDiscovery;
use Http\Discovery\Strategy\DiscoveryStrategy;
use Http\Promise\FulfilledPromise;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Mockery;
use Paddle\SDK\Notifications\Entities\Transaction;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

class PaddlePricingContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::shouldReceive('connection')->never();
    }

    public function test_checkout_sends_explicit_tax_inclusive_usd_price_without_discounts(): void
    {
        config(['paddle.api_key' => 'offline-test-key', 'paddle.environment' => 'sandbox']);
        $payment = $this->payment();
        $payment->setRelation('payable', new Order(['product_name_snapshot' => 'Test software']));
        $transport = Mockery::mock(HttpAsyncClient::class);
        $transport->shouldReceive('sendAsyncRequest')->once()->andReturnUsing(function ($request) {
            $this->assertSame('/transactions', $request->getUri()->getPath());
            $body = json_decode((string) $request->getBody(), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame('USD', $body['currency_code']);
            $this->assertCount(1, $body['items']);
            $this->assertSame(1, $body['items'][0]['quantity']);
            $this->assertSame('internal', $body['items'][0]['price']['tax_mode'] ?? null);
            $this->assertSame(['amount' => '10000', 'currency_code' => 'USD'], $body['items'][0]['price']['unit_price']);
            $this->assertArrayHasKey('discount_id', $body);
            $this->assertNull($body['discount_id']);
            $this->assertSame($this->transactionData()['custom_data'], $body['custom_data']);

            return new FulfilledPromise(new Response(201, ['Content-Type' => 'application/json'], json_encode([
                'data' => $this->transactionData(),
                'meta' => ['request_id' => 'offline-request'],
            ], JSON_THROW_ON_ERROR)));
        });

        $strategies = [...HttpAsyncClientDiscovery::getStrategies()];
        OfflinePaddlePricingDiscovery::$client = $transport;
        HttpAsyncClientDiscovery::prependStrategy(OfflinePaddlePricingDiscovery::class);

        try {
            $checkout = (new PaddleGateway(new PaddleClientFactory))->createCheckout($payment, 'Test software purchase');
            $this->assertSame('txn_offline', $checkout->providerPaymentId);
            $this->assertSame('internal', $checkout->metadata['tax_mode']);
        } finally {
            HttpAsyncClientDiscovery::setStrategies($strategies);
            OfflinePaddlePricingDiscovery::$client = null;
        }
    }

    #[DataProvider('configuredTaxCategories')]
    public function test_checkout_uses_configured_tax_category(
        string $payableType,
        string $configKey,
        string $configuredCategory
    ): void {
        config([
            'paddle.api_key' => 'offline-test-key',
            'paddle.environment' => 'sandbox',
            "paddle.tax_categories.{$configKey}" => $configuredCategory,
        ]);

        $payment = $this->payment();
        $payment->payable_type = $payableType;

        $payable = $payableType === CustomizationQuote::class
            ? new CustomizationQuote
            : new Order([
                'product_name_snapshot' => 'Test software',
            ]);

        $payment->setRelation('payable', $payable);

        $transport = Mockery::mock(HttpAsyncClient::class);

        $transport
            ->shouldReceive('sendAsyncRequest')
            ->once()
            ->andReturnUsing(function ($request) use ($configuredCategory) {
                $body = json_decode(
                    (string) $request->getBody(),
                    true,
                    flags: JSON_THROW_ON_ERROR
                );

                $this->assertSame(
                    $configuredCategory,
                    data_get(
                        $body,
                        'items.0.price.product.tax_category'
                    )
                );

                $transactionData = $this->transactionData();
                $transactionData['custom_data'] = $body['custom_data'];

                return new FulfilledPromise(
                    new Response(
                        201,
                        ['Content-Type' => 'application/json'],
                        json_encode([
                            'data' => $transactionData,
                            'meta' => [
                                'request_id' => 'offline-request',
                            ],
                        ], JSON_THROW_ON_ERROR)
                    )
                );
            });

        $strategies = [
            ...HttpAsyncClientDiscovery::getStrategies(),
        ];

        OfflinePaddlePricingDiscovery::$client = $transport;

        HttpAsyncClientDiscovery::prependStrategy(
            OfflinePaddlePricingDiscovery::class
        );

        try {
            $checkout = (
                new PaddleGateway(
                    new PaddleClientFactory
                )
            )->createCheckout(
                $payment,
                'Configured tax category test'
            );

            $this->assertSame(
                $configuredCategory,
                $checkout->metadata['tax_category']
            );
        } finally {
            HttpAsyncClientDiscovery::setStrategies($strategies);
            OfflinePaddlePricingDiscovery::$client = null;
        }
    }

    public static function configuredTaxCategories(): array
    {
        return [
            'product uses configured category' => [
                Order::class,
                'product',
                'software-programming-services',
            ],

            'customization uses configured category' => [
                CustomizationQuote::class,
                'customization',
                'standard',
            ],
        ];
    }
    #[DataProvider('acceptedTotals')]
    public function test_exact_total_is_accepted_with_or_without_included_tax(string $subtotal, string $tax): void
    {
        $data = $this->transactionData();
        $data['details']['totals']['subtotal'] = $subtotal;
        $data['details']['totals']['tax'] = $tax;

        $this->validate($this->payment(), $data);
        $this->addToAssertionCount(1);
    }

    public static function acceptedTotals(): array
    {
        return [
            'normal payment with zero tax' => ['10000', '0'],
            '20 percent included tax rounded to cents' => ['8333', '1667'],
            'tax exempt payment keeps the agreed gross price' => ['10000', '0'],
        ];
    }

    #[DataProvider('mismatchedTotals')]
    public function test_mismatched_total_is_rejected(string $total): void
    {
        $data = $this->transactionData();
        $data['details']['totals']['total'] = $total;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Paddle transaction total does not match the local payment amount.');

        $this->validate($this->payment(), $data);
    }

    public static function mismatchedTotals(): array
    {
        return [
            'underpayment' => ['9999'],
            'overpayment' => ['10001'],
            'tax incorrectly added on top' => ['12000'],
        ];
    }

    #[DataProvider('unsupportedDiscounts')]
    public function test_unexpected_discounts_are_rejected_even_if_total_matches(?string $id, string $discount): void
    {
        $data = $this->transactionData();
        $data['discount_id'] = $id;
        $data['details']['totals']['discount'] = $discount;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Paddle discounts are not supported for this payment.');

        $this->validate($this->payment(), $data);
    }

    public static function unsupportedDiscounts(): array
    {
        return [['dsc_unexpected', '0'], [null, '1000']];
    }

    public function test_non_usd_webhook_currency_is_rejected(): void
    {
        $data = $this->transactionData();
        $data['currency_code'] = 'EUR';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unexpected Paddle currency [EUR].');
        $this->validate($this->payment(), $data);
    }

    public function test_local_currency_mismatch_is_rejected(): void
    {
        $payment = $this->payment();
        $payment->currency = 'EUR';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Paddle transaction currency does not match the local payment.');
        $this->validate($payment, $this->transactionData());
    }

    #[DataProvider('identityFields')]
    public function test_custom_data_mismatches_are_still_rejected(string $field): void
    {
        $data = $this->transactionData();
        $data['custom_data'][$field] = 'wrong';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Paddle custom data mismatch for [{$field}].");
        $this->validate($this->payment(), $data);
    }

    public static function identityFields(): array
    {
        return array_map(fn ($field) => [$field], ['bizzsoft_payment_id', 'payment_number', 'payable_type', 'payable_id', 'user_id']);
    }

    private function validate(Payment $payment, array $data): void
    {
        // Test the real pricing boundary without persistence, migrations, or provider requests.
        (new ReflectionMethod(PaddleWebhookProcessor::class, 'validateTransaction'))
            ->invoke(new PaddleWebhookProcessor, $payment, Transaction::from($data));
    }

    private function payment(): Payment
    {
        return (new Payment)->forceFill([
            'id' => 1,
            'payment_number' => 'PAY-OFFLINE',
            'payable_type' => Order::class,
            'payable_id' => 2,
            'user_id' => 3,
            'amount' => '100.00',
            'currency' => 'USD',
            'provider' => 'paddle',
            'provider_payment_id' => 'txn_offline',
        ]);
    }

    private function transactionData(): array
    {
        return [
            'id' => 'txn_offline',
            'status' => 'completed',
            'origin' => 'api',
            'currency_code' => 'USD',
            'collection_mode' => 'automatic',
            'discount_id' => null,
            'custom_data' => [
                'bizzsoft_payment_id' => 1,
                'payment_number' => 'PAY-OFFLINE',
                'payable_type' => Order::class,
                'payable_id' => 2,
                'user_id' => 3,
            ],
            'details' => [
                'tax_rates_used' => [],
                'line_items' => [],
                'totals' => [
                    'subtotal' => '10000', 'discount' => '0', 'tax' => '0',
                    'total' => '10000', 'credit' => '0', 'balance' => '0',
                    'grand_total' => '10000', 'currency_code' => 'USD',
                    'credit_to_balance' => '0', 'grand_total_tax' => '0',
                ],
            ],
            'checkout' => ['url' => 'https://checkout.example.test/offline'],
            'created_at' => '2026-09-29T00:00:00Z',
            'updated_at' => '2026-09-29T00:00:00Z',
        ];
    }
}

final class OfflinePaddlePricingDiscovery implements DiscoveryStrategy
{
    public static ?HttpAsyncClient $client = null;

    public static function getCandidates($type): array
    {
        return $type === HttpAsyncClient::class
            ? [['class' => static fn () => self::$client]]
            : [];
    }
}
