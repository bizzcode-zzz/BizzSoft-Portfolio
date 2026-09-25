<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Ticket::class);

        $tickets = Ticket::query()
            ->with('user:id,name,email')
            ->withCount([
                'replies as unread_messages_count' => function ($query) {
                    $query
                        ->whereNull('read_at')
                        ->whereHas('user.roles', function ($query) {
                            $query->where('name', 'customer');
                        });
                },
            ])
            ->latest()
            ->get();

        return Inertia::render('Admin/Tickets/Index', [
            'tickets' => $tickets,
        ]);
    }

    public function show(Ticket $ticket): Response
    {
        $this->authorize('view', $ticket);

        $ticket->replies()
            ->whereNull('read_at')
            ->whereHas('user.roles', function ($query) {
                $query->where('name', 'customer');
            })
            ->update([
                'read_at' => now(),
            ]);

        $ticket->load([
            'user:id,name,email',

            'replies' => fn ($query) => $query
                ->with('user:id,name,email')
                ->oldest(),

            'secureAccesses' => fn ($query) => $query
                ->select([
                    'id',
                    'ticket_id',
                    'created_by',
                    'direction',
                    'type',
                    'label',
                    'status',
                    'submitted_at',
                    'viewed_at',
                    'closed_at',
                    'created_at',
                ])
                ->oldest(),
        ]);

        return Inertia::render('Admin/Tickets/Show', [
            'ticket' => $ticket,
        ]);
    }
}