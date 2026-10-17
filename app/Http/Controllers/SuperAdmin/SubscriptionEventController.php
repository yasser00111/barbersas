<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionEventController extends Controller
{
    public function index(Request $request): Response
    {
        $events = SubscriptionEvent::query()
            ->with(['tenant', 'subscription.plan'])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('SuperAdmin/Subscriptions/Index', [
            'events' => $events->through(fn (SubscriptionEvent $event) => [
                'id' => $event->id,
                'type' => $event->type->value,
                'tenant' => $event->tenant?->name,
                'plan' => $event->subscription?->plan?->name,
                'from_status' => $event->from_status,
                'to_status' => $event->to_status,
                'reason' => $event->reason,
                'created_at' => $event->created_at?->toIso8601String(),
            ]),
            'filters' => ['type' => $request->string('type')->toString()],
        ]);
    }
}
