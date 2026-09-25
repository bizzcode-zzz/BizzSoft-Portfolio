<?php

namespace App\Http\Controllers;

use App\Enums\TicketSecureAccessStatus;
use App\Models\Ticket;
use App\Models\TicketSecureAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TicketSecureAccessSubmissionController extends Controller
{
    public function store(
        Request $request,
        Ticket $ticket,
        TicketSecureAccess $secureAccess
    ): RedirectResponse {
        $this->authorize('view', $ticket);
        $this->authorize('submit', $secureAccess);

        abort_unless(
            $secureAccess->ticket_id === $ticket->id,
            404
        );

        abort_unless(
            $secureAccess->status === TicketSecureAccessStatus::Requested,
            422
        );

        $validated = $request->validate([
            'login_url' => [
                'nullable',
                'string',
                'max:2048',
            ],
            'username' => [
                'nullable',
                'string',
                'max:255',
            ],
            'secret' => [
                'required',
                'string',
                'max:10000',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $secureAccess->update([
            'login_url' => $validated['login_url'] ?? null,
            'username' => $validated['username'] ?? null,
            'secret' => $validated['secret'],
            'notes' => $validated['notes'] ?? null,
            'status' => TicketSecureAccessStatus::Submitted,
            'submitted_at' => now(),
            'viewed_at' => null,
            'closed_at' => null,
        ]);

        return back();
    }
}