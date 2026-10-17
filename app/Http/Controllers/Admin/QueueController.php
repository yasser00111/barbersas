<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QueueSource;
use App\Enums\QueueStatus;
use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WalkInQueueRequest;
use App\Models\Barber;
use App\Models\Queue;
use App\Models\Service;
use App\Services\QueueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class QueueController extends Controller
{
    public function __construct(protected QueueService $queues) {}

    public function index(Request $request): Response
    {
        $date = $request->date('date')?->toDateString() ?? now()->toDateString();

        $query = Queue::query()
            ->with(['customer', 'service', 'barber'])
            ->whereDate('queue_date', $date)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('queue_number');

        $counts = Queue::query()
            ->whereDate('queue_date', $date)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('Admin/Queues/Index', [
            'queues' => $query->get()->map(fn (Queue $queue) => [
                'id' => $queue->id,
                'uuid' => $queue->uuid,
                'number' => $queue->queue_number,
                'status' => $queue->status->value,
                'status_label' => $queue->status->label(),
                'source' => $queue->source->value,
                'customer' => $queue->customer?->name,
                'phone' => $queue->customer?->phone,
                'service' => $queue->service?->name,
                'barber' => $queue->barber?->name,
                'barber_id' => $queue->barber_id,
                'estimated_wait_minutes' => $queue->estimated_wait_minutes,
                'created_at' => $queue->created_at?->toIso8601String(),
            ]),
            'counts' => [
                'waiting' => (int) ($counts[QueueStatus::Waiting->value] ?? 0),
                'called' => (int) ($counts[QueueStatus::Called->value] ?? 0),
                'in_service' => (int) ($counts[QueueStatus::InService->value] ?? 0),
                'completed' => (int) ($counts[QueueStatus::Completed->value] ?? 0),
                'cancelled' => (int) (($counts[QueueStatus::Cancelled->value] ?? 0) + ($counts[QueueStatus::NoShow->value] ?? 0)),
            ],
            'filters' => [
                'date' => $date,
                'status' => $request->string('status')->toString(),
            ],
            'services' => Service::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']),
            'barbers' => Barber::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    public function store(WalkInQueueRequest $request): RedirectResponse
    {
        $tenant = $request->user()->tenant;

        if ($tenant->status !== TenantStatus::Active) {
            throw ValidationException::withMessages(['name' => 'Barbershop tidak aktif.']);
        }

        $service = Service::query()->findOrFail($request->integer('service_id'));
        $barber = $request->filled('barber_id') ? Barber::query()->findOrFail($request->integer('barber_id')) : null;

        $queue = $this->queues->create(
            tenant: $tenant,
            customerName: $request->string('name')->toString(),
            customerPhone: $request->string('phone')->toString(),
            service: $service,
            barber: $barber,
            source: QueueSource::WalkIn,
        );

        return back()->with('success', 'Antrian #'.$queue->queue_number.' dibuat.');
    }

    public function callNext(Request $request): RedirectResponse
    {
        $request->validate(['barber_id' => ['nullable', 'integer', 'exists:barbers,id']]);

        $queue = Queue::query()
            ->whereDate('queue_date', now()->toDateString())
            ->where('status', QueueStatus::Waiting)
            ->when($request->filled('barber_id'), fn ($q) => $q->where('barber_id', $request->integer('barber_id')))
            ->orderBy('queue_number')
            ->first();

        if (! $queue) {
            return back()->with('error', 'Tidak ada antrian yang menunggu.');
        }

        $this->queues->call($queue);

        return back()->with('success', 'Memanggil antrian #'.$queue->queue_number.'.');
    }

    public function call(Queue $queue): RedirectResponse
    {
        $this->queues->call($queue);

        return back()->with('success', 'Antrian #'.$queue->queue_number.' dipanggil.');
    }

    public function recall(Queue $queue): RedirectResponse
    {
        $this->queues->recall($queue);

        return back()->with('success', 'Antrian #'.$queue->queue_number.' dipanggil ulang.');
    }

    public function start(Request $request, Queue $queue): RedirectResponse
    {
        $request->validate(['barber_id' => ['nullable', 'integer', 'exists:barbers,id']]);

        $barber = $request->filled('barber_id') ? Barber::query()->find($request->integer('barber_id')) : null;

        $this->queues->start($queue, $barber);

        return back()->with('success', 'Layanan antrian #'.$queue->queue_number.' dimulai.');
    }

    public function complete(Queue $queue): RedirectResponse
    {
        $this->queues->complete($queue);

        return back()->with('success', 'Antrian #'.$queue->queue_number.' selesai.');
    }

    public function cancel(Request $request, Queue $queue): RedirectResponse
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        $this->queues->cancel($queue, $request->input('reason'));

        return back()->with('success', 'Antrian #'.$queue->queue_number.' dibatalkan.');
    }

    public function noShow(Queue $queue): RedirectResponse
    {
        $this->queues->markNoShow($queue);

        return back()->with('success', 'Antrian #'.$queue->queue_number.' ditandai tidak hadir.');
    }
}
