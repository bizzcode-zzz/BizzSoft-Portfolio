<?php

namespace Tests\Feature;

use App\Models\CustomizationQuote;
use App\Models\CustomizationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomizationQuoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_customization_quotes_table_has_expected_columns(): void
    {
        $this->assertTrue(
            Schema::hasColumns('customization_quotes', [
                'id',
                'customization_request_id',
                'price',
                'scope',
                'estimated_delivery',
                'created_at',
                'updated_at',
            ])
        );
    }

    public function test_customization_quote_belongs_to_customization_request(): void
    {
        $customizationRequest = CustomizationRequest::factory()->create();

        $quote = CustomizationQuote::factory()->create([
            'customization_request_id' => $customizationRequest->id,
        ]);

        $this->assertTrue(
            $quote->customizationRequest->is($customizationRequest)
        );
    }

    public function test_customization_request_has_one_quote(): void
    {
        $customizationRequest = CustomizationRequest::factory()->create();

        $quote = CustomizationQuote::factory()->create([
            'customization_request_id' => $customizationRequest->id,
        ]);

        $this->assertTrue(
            $customizationRequest->quote->is($quote)
        );
    }

    public function test_quote_price_is_cast_to_two_decimal_places(): void
    {
        $quote = CustomizationQuote::factory()->create([
            'price' => 12500.5,
        ]);

        $this->assertSame('12500.50', $quote->price);
    }

    public function test_estimated_delivery_is_cast_to_date(): void
    {
        $quote = CustomizationQuote::factory()->create([
            'estimated_delivery' => '2026-10-15',
        ]);

        $this->assertSame(
            '2026-10-15',
            $quote->estimated_delivery->toDateString()
        );
    }

    public function test_deleting_customization_request_deletes_its_quote(): void
    {
        $customizationRequest = CustomizationRequest::factory()->create();

        $quote = CustomizationQuote::factory()->create([
            'customization_request_id' => $customizationRequest->id,
        ]);

        $quoteId = $quote->id;

        $customizationRequest->delete();

        $this->assertDatabaseMissing('customization_quotes', [
            'id' => $quoteId,
        ]);
    }

    public function test_customization_request_cannot_have_multiple_quotes(): void
    {
        $customizationRequest = CustomizationRequest::factory()->create();

        CustomizationQuote::factory()->create([
            'customization_request_id' => $customizationRequest->id,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        CustomizationQuote::factory()->create([
            'customization_request_id' => $customizationRequest->id,
        ]);
    }
}