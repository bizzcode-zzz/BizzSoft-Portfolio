<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomizationRequest;
use Inertia\Inertia;
use Inertia\Response;

class CustomizationRequestController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', CustomizationRequest::class);

        $customizationRequests = CustomizationRequest::query()
            ->with('user:id,name,email')
            ->latest('created_at')
            ->latest('id')
            ->get();

        return Inertia::render('Admin/Customizations/Index', [
            'customizationRequests' => $customizationRequests,
        ]);
    }

    public function show(CustomizationRequest $customizationRequest): Response
    {
        $this->authorize('view', $customizationRequest);

        $customizationRequest->load([
            'user:id,name,email',
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

        return Inertia::render('Admin/Customizations/Show', [
            'customizationRequest' => $customizationRequest,
            'secureAccesses' => $secureAccesses,
        ]);
    }
}