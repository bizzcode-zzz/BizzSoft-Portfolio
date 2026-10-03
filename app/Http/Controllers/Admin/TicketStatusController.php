<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class TicketStatusController extends Controller
{
    public function resolve(Ticket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        if ($ticket->status === TicketStatus::Closed) {
            abort(422, 'Closed tickets cannot be resolved.');
        }

        DB::transaction(function () use ($ticket): void {
            $lockedTicket = Ticket::query()
                ->whereKey($ticket->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorize('update', $lockedTicket);

            if ($lockedTicket->status === TicketStatus::Closed) {
                abort(422, 'Closed tickets cannot be resolved.');
            }

            $lockedTicket->update([
                'status' => TicketStatus::Resolved,
            ]);
        });

        return redirect()->route('admin.tickets.show', $ticket);
    }

    public function close(Ticket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        if ($ticket->status !== TicketStatus::Resolved) {
            abort(422, 'Only resolved tickets can be closed.');
        }

        DB::transaction(function () use ($ticket): void {
            $lockedTicket = Ticket::query()
                ->whereKey($ticket->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorize('update', $lockedTicket);

            if ($lockedTicket->status !== TicketStatus::Resolved) {
                abort(422, 'Only resolved tickets can be closed.');
            }

            $lockedTicket->update([
                'status' => TicketStatus::Closed,
            ]);
        });

        return redirect()->route('admin.tickets.show', $ticket);
    }
}
