<?php

namespace App\Http\Middleware;

use App\Models\CustomizationMessage;
use App\Models\TicketReply;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        $customerUnreadTickets = 0;
        $customerUnreadCustomizations = 0;

        $adminUnreadTickets = 0;
        $adminUnreadCustomizations = 0;

        if ($user && $user->hasRole('customer')) {
            $customerUnreadTickets = TicketReply::query()
                ->whereNull('read_at')
                ->whereHas('ticket', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->whereHas('user.roles', function ($query) {
                    $query->where('name', 'admin');
                })
                ->count();

            $customerUnreadCustomizations = CustomizationMessage::query()
                ->whereNull('read_at')
                ->whereHas('customizationRequest', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->whereHas('user.roles', function ($query) {
                    $query->where('name', 'admin');
                })
                ->count();
        }

        if ($user && $user->hasRole('admin')) {
            $adminUnreadTickets = TicketReply::query()
                ->whereNull('read_at')
                ->whereHas('user.roles', function ($query) {
                    $query->where('name', 'customer');
                })
                ->count();

            $adminUnreadCustomizations = CustomizationMessage::query()
                ->whereNull('read_at')
                ->whereHas('user.roles', function ($query) {
                    $query->where('name', 'customer');
                })
                ->count();
        }

        return [
            ...parent::share($request),

            'auth' => [
                'user' => $user
                    ? [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'roles' => $user
                            ->getRoleNames()
                            ->values()
                            ->all(),
                    ]
                    : null,
            ],

            'customerUnread' => [
                'tickets' => $customerUnreadTickets,
                'customizations' => $customerUnreadCustomizations,
            ],

            'adminUnread' => [
                'tickets' => $adminUnreadTickets,
                'customizations' => $adminUnreadCustomizations,
            ],
        ];
    }
}