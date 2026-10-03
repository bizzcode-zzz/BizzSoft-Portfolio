<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Http\Controllers\Admin\TicketReplyController as AdminTicketReplyController;
use App\Http\Controllers\Admin\TicketStatusController;
use App\Http\Controllers\TicketReplyController as CustomerTicketReplyController;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\TestCase;

class TicketStaleTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_stale_admin_reply_cannot_reopen_ticket_closed_after_model_was_loaded(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create([
                'status' => TicketStatus::WaitingForAdmin,
            ]);

        $staleTicket = $ticket->fresh();

        Ticket::query()
            ->whereKey($ticket->id)
            ->update([
                'status' => TicketStatus::Closed->value,
            ]);

        $request = $this->requestFor($admin, [
            'message' => 'This stale reply must not reopen the ticket.',
        ]);

        $this->assertHttpStatus(
            422,
            fn () => app(AdminTicketReplyController::class)
                ->store($request, $staleTicket)
        );

        $this->assertDatabaseMissing('ticket_replies', [
            'ticket_id' => $ticket->id,
            'message' => 'This stale reply must not reopen the ticket.',
        ]);

        $this->assertSame(
            TicketStatus::Closed,
            $ticket->fresh()->status
        );
    }

    public function test_stale_customer_reply_cannot_reopen_ticket_closed_after_model_was_loaded(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create([
                'status' => TicketStatus::Resolved,
            ]);

        $staleTicket = $ticket->fresh();

        Ticket::query()
            ->whereKey($ticket->id)
            ->update([
                'status' => TicketStatus::Closed->value,
            ]);

        $request = $this->requestFor($customer, [
            'message' => 'This stale customer reply must be rejected.',
        ]);

        $this->assertHttpStatus(
            422,
            fn () => app(CustomerTicketReplyController::class)
                ->store($request, $staleTicket)
        );

        $this->assertDatabaseMissing('ticket_replies', [
            'ticket_id' => $ticket->id,
            'message' => 'This stale customer reply must be rejected.',
        ]);

        $this->assertSame(
            TicketStatus::Closed,
            $ticket->fresh()->status
        );
    }

    public function test_stale_close_cannot_overwrite_a_newer_non_resolved_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create([
                'status' => TicketStatus::Resolved,
            ]);

        $staleTicket = $ticket->fresh();

        Ticket::query()
            ->whereKey($ticket->id)
            ->update([
                'status' => TicketStatus::WaitingForAdmin->value,
            ]);

        $this->actingAs($admin);

        $this->assertHttpStatus(
            422,
            fn () => app(TicketStatusController::class)
                ->close($staleTicket)
        );

        $this->assertSame(
            TicketStatus::WaitingForAdmin,
            $ticket->fresh()->status
        );
    }

    public function test_stale_resolve_cannot_overwrite_a_newer_closed_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $ticket = Ticket::factory()
            ->for($customer)
            ->create([
                'status' => TicketStatus::WaitingForAdmin,
            ]);

        $staleTicket = $ticket->fresh();

        Ticket::query()
            ->whereKey($ticket->id)
            ->update([
                'status' => TicketStatus::Closed->value,
            ]);

        $this->actingAs($admin);

        $this->assertHttpStatus(
            422,
            fn () => app(TicketStatusController::class)
                ->resolve($staleTicket)
        );

        $this->assertSame(
            TicketStatus::Closed,
            $ticket->fresh()->status
        );
    }

    private function requestFor(User $user, array $data): Request
    {
        $this->actingAs($user);

        $request = Request::create('/stale-transition-test', 'POST', $data);

        $request->setUserResolver(
            fn (): User => $user
        );

        return $request;
    }

    private function assertHttpStatus(
        int $expectedStatus,
        callable $callback
    ): void {
        try {
            $callback();

            $this->fail(
                "Expected HTTP status {$expectedStatus}, but no exception was thrown."
            );
        } catch (HttpExceptionInterface $exception) {
            $this->assertSame(
                $expectedStatus,
                $exception->getStatusCode()
            );
        }
    }
}
