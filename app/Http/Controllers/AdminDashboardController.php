<?php

namespace App\Http\Controllers;

use App\Enums\CustomizationRequestStatus;
use App\Enums\TicketStatus;
use App\Models\CustomizationMessage;
use App\Models\TicketReply;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $openTickets = \App\Models\Ticket::query()
            ->whereNotIn('status', [
                TicketStatus::Resolved->value,
                TicketStatus::Closed->value,
            ])
            ->count();

        $unreadTicketMessages = TicketReply::query()
            ->whereNull('read_at')
            ->whereHas('user.roles', function ($query) {
                $query->where('name', 'customer');
            })
            ->count();

        $activeCustomizations = \App\Models\CustomizationRequest::query()
            ->whereNotIn('status', [
                CustomizationRequestStatus::QuoteDeclined->value,
                CustomizationRequestStatus::Completed->value,
                CustomizationRequestStatus::RequestDeclined->value,
                CustomizationRequestStatus::Cancelled->value,
            ])
            ->count();

        $unreadCustomizationMessages = CustomizationMessage::query()
            ->whereNull('read_at')
            ->whereHas('user.roles', function ($query) {
                $query->where('name', 'customer');
            })
            ->count();

        return Inertia::render('Admin/Dashboard', [
            'openTickets' => $openTickets,
            'unreadTicketMessages' => $unreadTicketMessages,
            'activeCustomizations' => $activeCustomizations,
            'unreadCustomizationMessages' => $unreadCustomizationMessages,

            'outstandingInvoices' => 0,
            'monthlyRevenue' => 0,
            'customers' => 0,
            'products' => 0,
            'orders' => 0,
            'pendingPayments' => 0,
        ]);
    }
}