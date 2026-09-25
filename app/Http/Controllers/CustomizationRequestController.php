<?php

namespace App\Http\Controllers;

use App\Enums\CustomizationRequestStatus;
use App\Models\CustomizationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomizationRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CustomizationRequest::class);

        $customizationRequests = $request->user()
            ->customizationRequests()
            ->withCount([
                'messages as unread_messages_count' => function ($query) {
                    $query
                        ->whereNull('read_at')
                        ->whereHas('user.roles', function ($query) {
                            $query->where('name', 'admin');
                        });
                },
            ])
            ->latest()
            ->get();

        return Inertia::render('Customer/Customizations/Index', [
            'customizationRequests' => $customizationRequests,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', CustomizationRequest::class);

        return Inertia::render('Customer/Customizations/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CustomizationRequest::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
        ]);

        $customizationRequest = $request->user()
            ->customizationRequests()
            ->create([
                ...$validated,
                'status' => CustomizationRequestStatus::Submitted,
            ]);

        return redirect()->route(
            'customizations.show',
            $customizationRequest,
        );
    }

    public function show(CustomizationRequest $customizationRequest): Response
    {
        $this->authorize('view', $customizationRequest);

        $customizationRequest->messages()
            ->whereNull('read_at')
            ->whereHas('user.roles', function ($query) {
                $query->where('name', 'admin');
            })
            ->update([
                'read_at' => now(),
            ]);

        $customizationRequest->load([
            'quote',
            'messages' => fn ($query) => $query
                ->with('user:id,name,email')
                ->oldest('created_at')
                ->oldest('id'),
        ]);

        $secureAccesses = $customizationRequest
            ->secureAccesses()
            ->select([
                'id',
                'customization_request_id',
                'created_by',
                'direction',
                'type',
                'label',
                'status',
                'submitted_at',
                'viewed_at',
                'closed_at',
                'created_at',
                'updated_at',
            ])
            ->oldest('created_at')
            ->oldest('id')
            ->get();

        return Inertia::render('Customer/Customizations/Show', [
            'customizationRequest' => $customizationRequest,
            'secureAccesses' => $secureAccesses,
        ]);
    }
}