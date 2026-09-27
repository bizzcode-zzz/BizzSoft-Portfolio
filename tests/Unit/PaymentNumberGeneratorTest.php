<?php

namespace Tests\Unit;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Payments\PaymentNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentNumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_payment_number_in_expected_format(): void
    {
        $number = app(PaymentNumberGenerator::class)->generate();

        $this->assertMatchesRegularExpression(
            '/^PAY-\d{8}-[A-Z0-9]{6}$/',
            $number
        );
    }

    public function test_it_retries_when_generated_payment_number_already_exists(): void
    {
        $user = User::factory()->create();

        $existingNumber = sprintf(
            'PAY-%s-ABC123',
            now()->format('Ymd')
        );

        Payment::create([
            'payment_number' => $existingNumber,
            'payable_type' => 'test',
            'payable_id' => 1,
            'user_id' => $user->id,
            'amount' => '1.00',
            'currency' => 'USD',
            'status' => PaymentStatus::Pending,
        ]);

        $values = [
            'ABC123',
            'DEF456',
        ];

        Str::createRandomStringsUsing(
            function () use (&$values): string {
                return array_shift($values);
            }
        );

        try {
            $number = app(PaymentNumberGenerator::class)->generate();
        } finally {
            Str::createRandomStringsNormally();
        }

        $this->assertSame(
            sprintf(
                'PAY-%s-DEF456',
                now()->format('Ymd')
            ),
            $number
        );

        $this->assertNotSame(
            $existingNumber,
            $number
        );
    }
}
