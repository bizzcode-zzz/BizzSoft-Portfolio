<?php

namespace App\Payments\Providers\Paddle;

use App\Models\CustomizationQuote;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\Data\CheckoutSession;
use App\Payments\Exceptions\CheckoutNotDispatched;
use App\Payments\Exceptions\CheckoutProviderRejected;
use InvalidArgumentException;
use Paddle\SDK\Exceptions\ApiError;
use Paddle\SDK\Entities\Shared\CollectionMode;
use Paddle\SDK\Entities\Shared\CurrencyCode;
use Paddle\SDK\Entities\Shared\CustomData;
use Paddle\SDK\Entities\Shared\Money;
use Paddle\SDK\Entities\Shared\TaxCategory;
use Paddle\SDK\Entities\Shared\TaxMode;
use Paddle\SDK\Entities\Transaction;
use Paddle\SDK\Resources\Transactions\Operations\Create\TransactionCreateItemWithPrice;
use Paddle\SDK\Resources\Transactions\Operations\CreateTransaction;
use Paddle\SDK\Resources\Transactions\Operations\Price\TransactionNonCatalogPriceWithProduct;
use Paddle\SDK\Resources\Transactions\Operations\Price\TransactionNonCatalogProduct;
use Throwable;

final class PaddleGateway implements PaymentGateway
{
    public function __construct(
        private readonly PaddleClientFactory $clientFactory,
    ) {}

    public function provider(): string
    {
        return 'paddle';
    }

    public function createCheckout(
        Payment $payment,
        string $description,
    ): CheckoutSession {
        try {
            $payment->loadMissing('payable');

            $currency = strtoupper(
                trim((string) $payment->currency)
            );

            if ($currency !== 'USD') {
                throw new InvalidArgumentException(
                    'Paddle payments must use USD.'
                );
            }

            $description = trim($description);

            if ($description === '') {
                throw new InvalidArgumentException(
                    'Paddle checkout description is required.'
                );
            }

            $currencyCode = new CurrencyCode('USD');

            $product = new TransactionNonCatalogProduct(
                name: $this->productNameFor($payment),
                taxCategory: new TaxCategory(
                    $this->taxCategoryFor($payment)
                ),
                description: $description,
            );

            $price = new TransactionNonCatalogPriceWithProduct(
                description: $description,
                unitPrice: new Money(
                    amount: $this->toMinorUnits(
                        (string) $payment->amount
                    ),
                    currencyCode: $currencyCode,
                ),
                product: $product,
                // The local amount is the final customer price, including applicable tax.
                taxMode: new TaxMode('internal'),
            );

            $item = new TransactionCreateItemWithPrice(
                price: $price,
                quantity: 1,
            );

            $customData = new CustomData([
                'bizzsoft_payment_id' => $payment->id,
                'payment_number' => $payment->payment_number,
                'payable_type' => $payment->payable_type,
                'payable_id' => $payment->payable_id,
                'user_id' => $payment->user_id,
            ]);
            $client = $this->clientFactory->make(retries: 0);
            $operation = new CreateTransaction(
                items: [$item],
                customData: $customData,
                discountId: null,
                currencyCode: $currencyCode,
                collectionMode: new CollectionMode('automatic'),
            );
        } catch (Throwable $exception) {
            throw new CheckoutNotDispatched($exception->getMessage(), 0, $exception);
        }

        // No automatic retry: a lost response does not prove that creation failed.
        try {
            $transaction = $client->transactions->create($operation);
        } catch (ApiError $exception) {
            if ($exception->type === 'request_error') {
                throw new CheckoutProviderRejected(
                    $exception->getMessage(),
                    0,
                    $exception
                );
            }

            throw $exception;
        }

        (new PaddleTransactionValidator)->validate($payment, $transaction);

        return $this->sessionFor($payment, $transaction);
    }

    public function sessionFor(Payment $payment, Transaction $transaction): CheckoutSession
    {
        // Preserve a known transaction ID even when Paddle has not supplied a usable URL.
        $checkoutUrl = $transaction->checkout?->url;

        if (
            strtolower((string) config('paddle.environment')) === 'sandbox' &&
            strtolower((string) parse_url($checkoutUrl, PHP_URL_HOST)) === 'localhost' &&
            strtolower((string) parse_url($checkoutUrl, PHP_URL_SCHEME)) === 'https'
        ) {
            $checkoutUrl = preg_replace(
                '/^https:\/\//i',
                'http://',
                $checkoutUrl,
                1
            );
        }

        return new CheckoutSession(
            provider: $this->provider(),
            providerPaymentId: $transaction->id,
            checkoutUrl: $checkoutUrl,
            metadata: [
                'currency' => 'USD',
                'tax_mode' => 'internal',
                'tax_category' => $this->taxCategoryFor(
                    $payment
                ),
            ],
        );
    }

    private function productNameFor(Payment $payment): string
    {
        return match (true) {
            $payment->payable instanceof Order => $payment->payable->product_name_snapshot,

            $payment->payable instanceof CustomizationQuote => 'BizzSoft Customization',

            default => "BizzSoft Payment {$payment->payment_number}",
        };
    }

    private function taxCategoryFor(Payment $payment): string
    {
        return match (true) {
            $payment->payable instanceof CustomizationQuote =>
                (string) config(
                    'paddle.tax_categories.customization',
                    'software-programming-services'
                ),

            default =>
                (string) config(
                    'paddle.tax_categories.product',
                    'standard'
                ),
        };
    }

    private function toMinorUnits(string $amount): string
    {
        $amount = trim($amount);

        if (
            ! preg_match(
                '/^\d+(?:\.\d{1,2})?$/',
                $amount
            )
        ) {
            throw new InvalidArgumentException(
                'Paddle payment amount must be a valid USD amount.'
            );
        }

        [$whole, $decimal] = array_pad(
            explode('.', $amount, 2),
            2,
            ''
        );

        $decimal = str_pad(
            $decimal,
            2,
            '0'
        );

        $minorUnits = ltrim(
            $whole.$decimal,
            '0'
        );

        if ($minorUnits === '') {
            throw new InvalidArgumentException(
                'Paddle payment amount must be greater than zero.'
            );
        }

        return $minorUnits;
    }
}
