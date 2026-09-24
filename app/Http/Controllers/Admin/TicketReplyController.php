<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketReplyController extends Controller
{
    public function store(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        if ($ticket->status === TicketStatus::Closed) {
            abort(422, 'Closed tickets cannot receive replies.');
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($request, $ticket, $validated) {
            $ticket->replies()->create([
                'user_id' => $request->user()->id,
                'message' => $validated['message'],
            ]);

            $ticket->update([
                'status' => TicketStatus::WaitingForCustomer,
            ]);
        });

        return redirect()->route('admin.tickets.show', $ticket);
    }
}