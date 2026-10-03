<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TicketSecureAccessStatus;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketSecureAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class TicketSecureAccessCloseController extends Controller
{
    public function close(
        Ticket $ticket,
        TicketSecureAccess $secureAccess
    ): RedirectResponse {
        abort_unless(
            $secureAccess->ticket_id === $ticket->id,
            404
        );

        $this->authorize('close', $secureAccess);

        abort_unless(
            $secureAccess->status !== TicketSecureAccessStatus::Closed,
            422
        );

        DB::transaction(function () use ($ticket, $secureAccess): void {
            $lockedSecureAccess = TicketSecureAccess::query()
                ->whereKey($secureAccess->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $lockedSecureAccess->ticket_id === $ticket->id,
                404
            );

            $this->authorize('close', $lockedSecureAccess);

            abort_unless(
                $lockedSecureAccess->status !== TicketSecureAccessStatus::Closed,
                422
            );

            $lockedSecureAccess->update([
                'login_url' => null,
                'username' => null,
                'secret' => null,
                'notes' => null,
                'status' => TicketSecureAccessStatus::Closed,
                'closed_at' => now(),
            ]);
        });

        return back();
    }
}
