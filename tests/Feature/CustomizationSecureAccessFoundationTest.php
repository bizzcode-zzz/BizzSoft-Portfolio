<?php

namespace Tests\Feature;

use App\Enums\CustomizationSecureAccessDirection;
use App\Enums\CustomizationSecureAccessStatus;
use App\Enums\CustomizationSecureAccessType;
use App\Models\CustomizationRequest;
use App\Models\CustomizationSecureAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomizationSecureAccessFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customization_request_can_have_secure_access_records(): void
    {
        $customizationRequest = CustomizationRequest::factory()->create();
        $creator = User::factory()->create();

        $secureAccess = CustomizationSecureAccess::factory()->create([
            'customization_request_id' => $customizationRequest->id,
            'created_by' => $creator->id,
        ]);

        $this->assertTrue(
            $customizationRequest->secureAccesses->contains($secureAccess)
        );
    }

    public function test_secure_access_belongs_to_customization_request_and_creator(): void
    {
        $customizationRequest = CustomizationRequest::factory()->create();
        $creator = User::factory()->create();

        $secureAccess = CustomizationSecureAccess::factory()->create([
            'customization_request_id' => $customizationRequest->id,
            'created_by' => $creator->id,
        ]);

        $this->assertTrue(
            $secureAccess->customizationRequest->is($customizationRequest)
        );

        $this->assertTrue(
            $secureAccess->creator->is($creator)
        );
    }

    public function test_secure_access_uses_expected_enum_casts(): void
    {
        $secureAccess = CustomizationSecureAccess::factory()->create([
            'direction' => CustomizationSecureAccessDirection::AdminToCustomer,
            'type' => CustomizationSecureAccessType::ApiToken,
            'status' => CustomizationSecureAccessStatus::Submitted,
        ]);

        $secureAccess->refresh();

        $this->assertSame(
            CustomizationSecureAccessDirection::AdminToCustomer,
            $secureAccess->direction
        );

        $this->assertSame(
            CustomizationSecureAccessType::ApiToken,
            $secureAccess->type
        );

        $this->assertSame(
            CustomizationSecureAccessStatus::Submitted,
            $secureAccess->status
        );
    }

    public function test_sensitive_secure_access_values_are_encrypted_at_rest(): void
    {
        $secureAccess = CustomizationSecureAccess::factory()->create([
            'login_url' => 'https://hosting.example.com/private-login',
            'username' => 'secure-customer',
            'secret' => 'super-secret-password',
            'notes' => 'Temporary production access only.',
        ]);

        $stored = DB::table('customization_secure_accesses')
            ->where('id', $secureAccess->id)
            ->first();

        $this->assertNotSame(
            'https://hosting.example.com/private-login',
            $stored->login_url
        );

        $this->assertNotSame(
            'secure-customer',
            $stored->username
        );

        $this->assertNotSame(
            'super-secret-password',
            $stored->secret
        );

        $this->assertNotSame(
            'Temporary production access only.',
            $stored->notes
        );

        $secureAccess->refresh();

        $this->assertSame(
            'https://hosting.example.com/private-login',
            $secureAccess->login_url
        );

        $this->assertSame(
            'secure-customer',
            $secureAccess->username
        );

        $this->assertSame(
            'super-secret-password',
            $secureAccess->secret
        );

        $this->assertSame(
            'Temporary production access only.',
            $secureAccess->notes
        );
    }

    public function test_sensitive_fields_can_be_null_after_credentials_are_purged(): void
    {
        $secureAccess = CustomizationSecureAccess::factory()->create();

        $secureAccess->update([
            'login_url' => null,
            'username' => null,
            'secret' => null,
            'notes' => null,
            'status' => CustomizationSecureAccessStatus::Closed,
            'closed_at' => now(),
        ]);

        $secureAccess->refresh();

        $this->assertNull($secureAccess->login_url);
        $this->assertNull($secureAccess->username);
        $this->assertNull($secureAccess->secret);
        $this->assertNull($secureAccess->notes);

        $this->assertSame(
            CustomizationSecureAccessStatus::Closed,
            $secureAccess->status
        );

        $this->assertNotNull($secureAccess->closed_at);
    }
}