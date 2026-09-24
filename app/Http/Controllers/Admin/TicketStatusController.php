<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;

class TicketStatusController extends Controller
{
    public function resolve(Ticket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        if ($ticket->status === TicketStatus::Closed) {
            abort(422, 'Closed tickets cannot be resolved.');
        }

        $ticket->update([
            'status' => TicketStatus::Resolved,
        ]);

        return redirect()->route('admin.tickets.show', $ticket);
    }

    public function close(Ticket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        if ($ticket->status !== TicketStatus::Resolved) {
            abort(422, 'Only resolved tickets can be closed.');
        }

        $ticket->update([
            'status' => TicketStatus::Closed,
        ]);

        return redirect()->route('admin.tickets.show', $ticket);
    }
}