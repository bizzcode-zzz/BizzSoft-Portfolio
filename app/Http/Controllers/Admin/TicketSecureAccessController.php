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

class TicketSecureAccessController extends Controller
{
    public function store(Request $request, Ticket $ticket): RedirectResponse
    {
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
        ]);

        $ticket->secureAccesses()->create([
            'created_by' => $request->user()->id,
            'direction' => TicketSecureAccessDirection::CustomerToAdmin,
            'type' => $validated['type'],
            'label' => $validated['label'],
            'login_url' => null,
            'username' => null,
            'secret' => null,
            'notes' => null,
            'status' => TicketSecureAccessStatus::Requested,
            'submitted_at' => null,
            'viewed_at' => null,
            'closed_at' => null,
        ]);

        return back();
    }
}