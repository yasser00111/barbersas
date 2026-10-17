<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Models\Barber;
use App\Models\Service;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function __construct(protected AuditLogger $audit) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Services/Index', [
            'services' => Service::query()
                ->with('barbers:id')
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Service $service) => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'description' => $service->description,
                    'price' => (float) $service->price,
                    'duration_minutes' => $service->duration_minutes,
                    'is_active' => $service->is_active,
                    'sort_order' => $service->sort_order,
                    'barber_ids' => $service->barbers->pluck('id'),
                ]),
            'barbers' => Barber::query()->orderBy('sort_order')->get(['id', 'name', 'is_active']),
        ]);
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $service = Service::create(array_merge(
                $request->safe()->except('barber_ids'),
                ['tenant_id' => $request->user()->tenant_id],
            ));

            $service->barbers()->sync($request->input('barber_ids', []));

            $this->audit->log('service.created', $service, 'Layanan ditambahkan: '.$service->name, [], $service->only(['name', 'price']));
        });

        return back()->with('success', 'Layanan berhasil ditambahkan.');
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $this->authorize('update', $service->tenant);

        DB::transaction(function () use ($request, $service) {
            $old = $service->only(['name', 'price', 'duration_minutes', 'is_active']);

            $service->update($request->safe()->except('barber_ids'));
            $service->barbers()->sync($request->input('barber_ids', []));

            $this->audit->log('service.updated', $service, 'Layanan diperbarui: '.$service->name, $old, $service->only(['name', 'price', 'duration_minutes', 'is_active']));
        });

        return back()->with('success', 'Layanan berhasil diperbarui.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $this->authorize('update', $service->tenant);

        $service->delete();

        $this->audit->log('service.deleted', null, 'Layanan dihapus: '.$service->name);

        return back()->with('success', 'Layanan dihapus.');
    }
}
