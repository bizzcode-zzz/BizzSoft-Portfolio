<?php

namespace App\Http\Controllers;

use App\Enums\TicketSecureAccessStatus;
use App\Models\Ticket;
use App\Models\TicketSecureAccess;
use Illuminate\Http\JsonResponse;

class TicketSecureAccessRevealController extends Controller
{
    public function show(
        Ticket $ticket,
        TicketSecureAccess $secureAccess
    ): JsonResponse {
        abort_unless(
            $secureAccess->ticket_id === $ticket->id,
            404
        );

        $this->authorize('reveal', $secureAccess);

        abort_unless(
            $secureAccess->status === TicketSecureAccessStatus::Submitted,
            422
        );

        if ($secureAccess->viewed_at === null) {
            $secureAccess->update([
                'viewed_at' => now(),
            ]);
        }

        return response()->json([
            'login_url' => $secureAccess->login_url,
            'username' => $secureAccess->username,
            'secret' => $secureAccess->secret,
            'notes' => $secureAccess->notes,
        ]);
    }
}