<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $customers = Customer::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search')->toString();
                $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
            })
            ->orderByDesc('last_visit_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers->through(fn (Customer $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'visits_count' => $customer->visits_count,
                'last_visit_at' => $customer->last_visit_at?->toIso8601String(),
            ]),
            'filters' => ['search' => $request->string('search')->toString()],
        ]);
    }

    public function show(Customer $customer): Response
    {
        $this->authorize('update', $customer->tenant);

        return Inertia::render('Admin/Customers/Show', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'notes' => $customer->notes,
                'visits_count' => $customer->visits_count,
                'last_visit_at' => $customer->last_visit_at?->toIso8601String(),
            ],
            'history' => $customer->queues()
                ->with(['service', 'barber'])
                ->latest('queue_date')
                ->limit(50)
                ->get()
                ->map(fn ($queue) => [
                    'id' => $queue->id,
                    'date' => $queue->queue_date?->toDateString(),
                    'number' => $queue->queue_number,
                    'service' => $queue->service?->name,
                    'barber' => $queue->barber?->name,
                    'status' => $queue->status->value,
                    'status_label' => $queue->status->label(),
                ]),
        ]);
    }
}
