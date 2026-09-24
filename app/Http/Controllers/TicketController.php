<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Enums\TicketStatus;

class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Ticket::class);

        $tickets = $request->user()
            ->tickets()
            ->latest()
            ->get();

        return Inertia::render('Customer/Tickets/Index', [
            'tickets' => $tickets,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Ticket::class);

        return Inertia::render('Customer/Tickets/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Ticket::class);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $ticket = $request->user()
            ->tickets()
            ->create([
                ...$validated,
                'status' => TicketStatus::WaitingForAdmin,
            ]);

        return redirect()->route('tickets.show', $ticket);
    }

    public function show(Ticket $ticket): Response
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'replies' => fn($query) => $query
                ->with('user:id,name')
                ->oldest(),
        ]);

        return Inertia::render('Customer/Tickets/Show', [
            'ticket' => $ticket,
        ]);
    }
}
