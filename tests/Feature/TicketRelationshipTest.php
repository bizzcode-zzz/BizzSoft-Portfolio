<?php

namespace Tests\Feature;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_have_many_tickets(): void
    {
        $user = User::factory()->create();

        Ticket::factory()
            ->count(2)
            ->for($user)
            ->create();

        $this->assertCount(2, $user->tickets);
    }

    public function test_ticket_belongs_to_a_user(): void
    {
        $user = User::factory()->create();

        $ticket = Ticket::factory()
            ->for($user)
            ->create();

        $this->assertTrue($ticket->user->is($user));
    }

    public function test_ticket_can_have_many_replies(): void
    {
        $ticket = Ticket::factory()->create();

        TicketReply::factory()
            ->count(3)
            ->for($ticket)
            ->create();

        $this->assertCount(3, $ticket->replies);
    }

    public function test_ticket_reply_belongs_to_a_ticket(): void
    {
        $ticket = Ticket::factory()->create();

        $reply = TicketReply::factory()
            ->for($ticket)
            ->create();

        $this->assertTrue($reply->ticket->is($ticket));
    }

    public function test_ticket_reply_belongs_to_a_user(): void
    {
        $user = User::factory()->create();

        $reply = TicketReply::factory()
            ->for($user)
            ->create();

        $this->assertTrue($reply->user->is($user));
    }

    public function test_user_can_have_many_ticket_replies(): void
    {
        $user = User::factory()->create();

        TicketReply::factory()
            ->count(2)
            ->for($user)
            ->create();

        $this->assertCount(2, $user->ticketReplies);
    }

    public function test_deleting_ticket_also_deletes_its_replies(): void
    {
        $ticket = Ticket::factory()->create();

        TicketReply::factory()
            ->count(3)
            ->for($ticket)
            ->create();

        $ticketId = $ticket->id;

        $this->assertDatabaseCount('ticket_replies', 3);

        $ticket->delete();

        $this->assertDatabaseMissing('tickets', [
            'id' => $ticketId,
        ]);

        $this->assertDatabaseCount('ticket_replies', 0);
    }

    public function test_new_ticket_defaults_to_waiting_for_admin_status(): void
    {
        $user = User::factory()->create();

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'subject' => 'Need help with my script',
            'message' => 'I need assistance with my purchased script.',
        ]);

        $ticket->refresh();

        $this->assertSame(
            TicketStatus::WaitingForAdmin,
            $ticket->status
        );

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => TicketStatus::WaitingForAdmin->value,
        ]);
    }
}