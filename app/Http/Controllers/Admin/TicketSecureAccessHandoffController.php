<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketSecureAccessDirection;
use App\Enums\TicketSecureAccessStatus;
use App\Enums\TicketSecureAccessType;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TicketSecureAccessHandoffController extends Controller
{
    public function store(
        Request $request,
        Ticket $ticket
    ): RedirectResponse {
        $this->authorize('update', $ticket);

        $validated = $request->validate([
            'type' => [
                'required',
                Rule::enum(TicketSecureAccessType::class),
            ],
            'label' => [
                'required',
                'string',
                'max:255',
            ],
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

        $ticket->secureAccesses()->create([
            'created_by' => $request->user()->id,
            'direction' => TicketSecureAccessDirection::AdminToCustomer,
            'type' => $validated['type'],
            'label' => $validated['label'],
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