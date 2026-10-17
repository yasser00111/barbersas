<?php

namespace App\Http\Controllers\Public;

use App\Enums\QueueSource;
use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreQueueRequest;
use App\Models\Barber;
use App\Models\Queue;
use App\Models\Service;
use App\Models\Tenant;
use App\Services\QueueService;
use App\Support\TenantSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class QueueController extends Controller
{
    public function __construct(protected QueueService $queues) {}

    public function create(Tenant $tenant): Response
    {
        $availability = $this->queues->availability($tenant, null, null);

        $tenant->load(['services' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'), 'barbers' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')]);

        return Inertia::render('Public/QueueCreate', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'phone' => $tenant->phone,
                'address' => $tenant->address,
            ],
            'services' => $tenant->services->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'price' => (float) $service->price,
                'duration_minutes' => $service->duration_minutes,
            ]),
            'barbers' => $tenant->barbers->map(fn (Barber $barber) => [
                'id' => $barber->id,
                'name' => $barber->name,
                'service_ids' => $barber->services()->pluck('services.id'),
            ]),
            'available' => $availability['ok'],
            'unavailableReason' => $availability['reason'],
            'requireBarberSelection' => (bool) TenantSettings::for($tenant)->get('require_barber_selection'),
        ]);
    }

    public function store(StoreQueueRequest $request, Tenant $tenant): RedirectResponse
    {
        if ($tenant->status !== TenantStatus::Active) {
            throw ValidationException::withMessages(['service_id' => 'Barbershop sedang tidak aktif.']);
        }

        $service = Service::query()
            ->where('tenant_id', $tenant->getKey())
            ->where('is_active', true)
            ->findOrFail($request->integer('service_id'));

        $barber = $request->filled('barber_id')
            ? Barber::query()->where('tenant_id', $tenant->getKey())->where('is_active', true)->findOrFail($request->integer('barber_id'))
            : null;

        $availability = $this->queues->availability($tenant, $service, $barber);

        if (! $availability['ok']) {
            throw ValidationException::withMessages(['service_id' => $availability['reason']]);
        }

        $queue = $this->queues->create(
            tenant: $tenant,
            customerName: $request->string('name')->toString(),
            customerPhone: $request->string('phone')->toString(),
            service: $service,
            barber: $barber,
            source: QueueSource::Online,
            email: $request->input('email'),
        );

        return redirect()->route('public.queue.status', [
            'tenant' => $tenant->slug,
            'queueNumber' => $queue->queue_number,
        ])->with('success', 'Antrian berhasil dibuat. Nomor antrian Anda '.$queue->queue_number.'.');
    }

    public function status(Tenant $tenant, int $queueNumber): Response
    {
        $queue = $this->findQueue($tenant, $queueNumber);

        return Inertia::render('Public/QueueStatus', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'phone' => $tenant->phone,
                'address' => $tenant->address,
            ],
            'queue' => $queue ? $this->queuePayload($tenant, $queue) : null,
            'queueNumber' => $queueNumber,
            'allowCancel' => (bool) TenantSettings::for($tenant)->get('allow_online_cancel'),
        ]);
    }

    public function poll(Tenant $tenant, int $queueNumber): \Illuminate\Http\JsonResponse
    {
        $queue = $this->findQueue($tenant, $queueNumber);

        return response()->json([
            'queue' => $queue ? $this->queuePayload($tenant, $queue) : null,
        ]);
    }

    public function cancel(Tenant $tenant, int $queueNumber): RedirectResponse
    {
        if (! TenantSettings::for($tenant)->get('allow_online_cancel')) {
            return back()->with('error', 'Pembatalan antrian online dinonaktifkan.');
        }

        $queue = $this->findQueue($tenant, $queueNumber);

        if (! $queue || $queue->status->isFinished()) {
            return back()->with('error', 'Antrian tidak dapat dibatalkan.');
        }

        $this->queues->cancel($queue, 'Dibatalkan oleh pelanggan.');

        return redirect()->route('public.queue.status', [
            'tenant' => $tenant->slug,
            'queueNumber' => $queueNumber,
        ])->with('success', 'Antrian dibatalkan.');
    }

    protected function findQueue(Tenant $tenant, int $queueNumber): ?Queue
    {
        return Queue::withoutTenantScope()
            ->with(['service', 'barber'])
            ->where('tenant_id', $tenant->getKey())
            ->whereDate('queue_date', now($tenant->timezone)->toDateString())
            ->where('queue_number', $queueNumber)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    protected function queuePayload(Tenant $tenant, Queue $queue): array
    {
        $ahead = Queue::withoutTenantScope()
            ->where('tenant_id', $tenant->getKey())
            ->where('queue_date', $queue->queue_date)
            ->where('queue_number', '<', $queue->queue_number)
            ->whereIn('status', ['waiting', 'called', 'in_service'])
            ->when($queue->barber_id, fn ($q) => $q->where('barber_id', $queue->barber_id))
            ->count();

        $serving = Queue::withoutTenantScope()
            ->where('tenant_id', $tenant->getKey())
            ->where('queue_date', $queue->queue_date)
            ->whereIn('status', ['called', 'in_service'])
            ->orderBy('queue_number')
            ->first();

        return [
            'number' => $queue->queue_number,
            'status' => $queue->status->value,
            'status_label' => $queue->status->label(),
            'position_ahead' => $ahead,
            'estimated_wait_minutes' => $queue->estimated_wait_minutes,
            'service' => $queue->service?->name,
            'barber' => $queue->barber?->name,
            'serving_number' => $serving?->queue_number,
            'created_at' => $queue->created_at?->toIso8601String(),
        ];
    }
}
