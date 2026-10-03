<?php

namespace Tests\Feature;

use App\Enums\CustomizationSecureAccessDirection;
use App\Enums\CustomizationSecureAccessStatus;
use App\Enums\CustomizationSecureAccessType;
use App\Enums\TicketSecureAccessStatus;
use App\Enums\TicketSecureAccessType;
use App\Http\Controllers\Admin\CustomizationSecureAccessHandoffController;
use App\Http\Controllers\Admin\TicketSecureAccessHandoffController;
use App\Http\Controllers\CustomizationSecureAccessSubmissionController;
use App\Http\Controllers\TicketSecureAccessSubmissionController;
use App\Models\CustomizationRequest;
use App\Models\CustomizationSecureAccess;
use App\Models\Ticket;
use App\Models\TicketSecureAccess;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SecureAccessFlashInputTest extends TestCase
{
    #[DataProvider('validationFailures')]
    public function test_credentials_are_not_flashed_on_validation_failure(
        string $flow,
        bool $inertia,
        string $invalidField,
    ): void {
        // Exercise real controller validation and exception handling without database access.
        DB::shouldReceive('connection')->never();
        Gate::before(static fn (?User $user) => true);

        Route::middleware('web')->post('/_test/secure-access', function (Request $request) use ($flow) {
            $ticket = (new Ticket)->forceFill(['id' => 1]);
            $customization = (new CustomizationRequest)->forceFill(['id' => 1]);

            return match ($flow) {
                'ticket_submission' => app(TicketSecureAccessSubmissionController::class)->store(
                    $request,
                    $ticket,
                    (new TicketSecureAccess)->forceFill([
                        'ticket_id' => 1,
                        'status' => TicketSecureAccessStatus::Requested,
                    ]),
                ),
                'customization_submission' => app(CustomizationSecureAccessSubmissionController::class)->store(
                    $request,
                    $customization,
                    (new CustomizationSecureAccess)->forceFill([
                        'direction' => CustomizationSecureAccessDirection::CustomerToAdmin,
                        'status' => CustomizationSecureAccessStatus::Requested,
                    ]),
                ),
                'ticket_handoff' => app(TicketSecureAccessHandoffController::class)->store($request, $ticket),
                'customization_handoff' => app(CustomizationSecureAccessHandoffController::class)->store($request, $customization),
            };
        });

        $payload = [
            'label' => 'Temporary access',
            'type' => str_starts_with($flow, 'ticket')
                ? TicketSecureAccessType::cases()[0]->value
                : CustomizationSecureAccessType::cases()[0]->value,
            'login_url' => 'https://private.example.test/login?token=sensitive-token',
            'username' => 'private-support-user',
            'secret' => 'private-support-secret',
            'notes' => 'Private recovery instructions',
            'password' => 'private-password',
            'current_password' => 'private-current-password',
            'password_confirmation' => 'private-password-confirmation',
        ];
        $payload[$invalidField] = str_repeat('sensitive-', 600);

        $headers = ['Accept' => 'text/html, application/xhtml+xml'];
        if ($inertia) {
            $headers['X-Inertia'] = 'true';
        }

        $response = $this->from('/secure-access-form')->post('/_test/secure-access', $payload, $headers);

        $response->assertRedirect('/secure-access-form')
            ->assertSessionHasErrors($invalidField)
            ->assertSessionHasInput('label', 'Temporary access')
            ->assertSessionHasInput('type', $payload['type']);

        foreach (['login_url', 'username', 'secret', 'notes', 'password', 'current_password', 'password_confirmation'] as $field) {
            $response->assertSessionMissing('_old_input.'.$field);
            $this->assertStringNotContainsString(
                $payload[$field],
                serialize(session()->all()),
                "Sensitive {$field} must not be stored anywhere in the session.",
            );
        }
    }

    public static function validationFailures(): iterable
    {
        foreach (['ticket_submission', 'customization_submission', 'ticket_handoff', 'customization_handoff'] as $flow) {
            foreach ([false, true] as $inertia) {
                foreach (['username', 'notes'] as $invalidField) {
                    yield $flow.($inertia ? '_inertia_' : '_browser_').$invalidField => [$flow, $inertia, $invalidField];
                }
            }
        }
    }
}
