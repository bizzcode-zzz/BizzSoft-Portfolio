<?php

namespace App\Http\Controllers;

use App\Enums\CustomizationRequestStatus;
use App\Enums\TicketStatus;
use App\Models\CustomizationMessage;
use App\Models\TicketReply;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $activeTickets = $user
            ->tickets()
            ->whereNotIn('status', [
                TicketStatus::Resolved->value,
                TicketStatus::Closed->value,
            ])
            ->count();

        $unreadTicketMessages = TicketReply::query()
            ->whereNull('read_at')
            ->whereHas('ticket', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->whereHas('user.roles', function ($query) {
                $query->where('name', 'admin');
            })
            ->count();

        $activeCustomizations = $user
            ->customizationRequests()
            ->whereNotIn('status', [
                CustomizationRequestStatus::QuoteDeclined->value,
                CustomizationRequestStatus::Completed->value,
                CustomizationRequestStatus::RequestDeclined->value,
                CustomizationRequestStatus::Cancelled->value,
            ])
            ->count();

        $unreadCustomizationMessages = CustomizationMessage::query()
            ->whereNull('read_at')
            ->whereHas('customizationRequest', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->whereHas('user.roles', function ($query) {
                $query->where('name', 'admin');
            })
            ->count();

        return Inertia::render('Customer/Dashboard', [
            'activeTickets' => $activeTickets,
            'unreadTicketMessages' => $unreadTicketMessages,
            'activeCustomizations' => $activeCustomizations,
            'unreadCustomizationMessages' => $unreadCustomizationMessages,
        ]);
    }
}