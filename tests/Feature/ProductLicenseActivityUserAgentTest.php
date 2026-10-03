<?php

namespace Tests\Feature;

use App\Models\ProductLicenseActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductLicenseActivityUserAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_oversized_user_agent_is_truncated_during_license_validation(): void
    {
        $userAgent = 'Validation-Agent/'.str_repeat('V', 1000);

        $this
            ->withHeader('User-Agent', $userAgent)
            ->postJson('/api/licenses/validate', [
                'license_key' => 'BIZZ-INVALID-USER-AGENT-0001',
                'product_key' => '11111111-1111-4111-8111-111111111111',
                'domain' => 'company-a.test',
            ])
            ->assertStatus(404);

        $activity = ProductLicenseActivity::query()->sole();

        $this->assertSame(
            ProductLicenseActivity::USER_AGENT_MAX_LENGTH,
            mb_strlen((string) $activity->user_agent)
        );

        $this->assertSame(
            mb_substr(
                $userAgent,
                0,
                ProductLicenseActivity::USER_AGENT_MAX_LENGTH
            ),
            $activity->user_agent
        );

        $this->assertSame(
            'invalid_license',
            $activity->event
        );
    }

    public function test_oversized_user_agent_is_truncated_during_license_activation(): void
    {
        $userAgent = 'Activation-Agent/'.str_repeat('A', 1000);

        $this
            ->withHeader('User-Agent', $userAgent)
            ->postJson('/api/licenses/activate', [
                'license_key' => 'BIZZ-INVALID-USER-AGENT-0002',
                'product_key' => '22222222-2222-4222-8222-222222222222',
                'domain' => 'company-a.test',
            ])
            ->assertStatus(404);

        $activity = ProductLicenseActivity::query()->sole();

        $this->assertSame(
            ProductLicenseActivity::USER_AGENT_MAX_LENGTH,
            mb_strlen((string) $activity->user_agent)
        );

        $this->assertSame(
            mb_substr(
                $userAgent,
                0,
                ProductLicenseActivity::USER_AGENT_MAX_LENGTH
            ),
            $activity->user_agent
        );

        $this->assertSame(
            'invalid_license',
            $activity->event
        );
    }

    public function test_null_user_agent_remains_null(): void
    {
        $activity = ProductLicenseActivity::create([
            'product_license_id' => null,
            'event' => 'invalid_license',
            'attempted_domain' => 'company-a.test',
            'license_key_fingerprint' => hash(
                'sha256',
                'BIZZ-NULL-USER-AGENT'
            ),
            'ip_address' => '127.0.0.1',
            'user_agent' => null,
            'http_status' => 404,
        ]);

        $this->assertNull(
            $activity->fresh()->user_agent
        );
    }
}
