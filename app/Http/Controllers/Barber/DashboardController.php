<?php

namespace App\Http\Controllers\Barber;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Models\Barber;
use App\Models\Queue;
use App\Services\QueueService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Barber-facing console. A barber may only operate on queues that belong to
 * their own tenant and are assigned to them (or unassigned).
 */
class DashboardController extends Controller
{
    public function __construct(protected QueueService $queues) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $barber = $user->barber;

        $today = now($user->tenant->timezone)->toDateString();

        $counts = Queue::query()
            ->whereDate('queue_date', $today)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $mine = fn () => Queue::query()
            ->with(['customer', 'service'])
            ->whereDate('queue_date', $today)
            ->where(fn ($q) => $q->where('barber_id', $barber?->getKey())->orWhereNull('barber_id'));

        return Inertia::render('Barber/Dashboard', [
            'barber' => $barber ? ['id' => $barber->getKey(), 'name' => $barber->name] : null,
            'stats' => [
                'waiting' => (int) ($counts[QueueStatus::Waiting->value] ?? 0),
                'in_service' => (int) ($counts[QueueStatus::InService->value] ?? 0),
                'completed' => (int) ($counts[QueueStatus::Completed->value] ?? 0),
            ],
            'queues' => $mine()
                ->whereIn('status', [QueueStatus::Waiting->value, QueueStatus::Called->value, QueueStatus::InService->value])
                ->orderBy('queue_number')
                ->get()
                ->map(fn (Queue $queue) => [
                    'id' => $queue->getKey(),
                    'uuid' => $queue->uuid,
                    'number' => $queue->queue_number,
                    'status' => $queue->status->value,
                    'status_label' => $queue->status->label(),
                    'customer' => $queue->customer?->name,
                    'phone' => $queue->customer?->phone,
                    'service' => $queue->service?->name,
                    'barber_id' => $queue->barber_id,
                ]),
        ]);
    }

    public function call(Request $request, Queue $queue): RedirectResponse
    {
        $this->ensureOwned($request, $queue);
        $this->queues->call($queue);

        return back()->with('success', 'Antrian #'.$queue->queue_number.' dipanggil.');
    }

    public function start(Request $request, Queue $queue): RedirectResponse
    {
        $this->ensureOwned($request, $queue);
        $this->queues->start($queue, $request->user()->barber);

        return back()->with('success', 'Layanan antrian #'.$queue->queue_number.' dimulai.');
    }

    public function complete(Request $request, Queue $queue): RedirectResponse
    {
        $this->ensureOwned($request, $queue);
        $this->queues->complete($queue);

        return back()->with('success', 'Antrian #'.$queue->queue_number.' selesai.');
    }

    protected function ensureOwned(Request $request, Queue $queue): void
    {
        $barber = $request->user()->barber;

        abort_unless($queue->barber_id === null || $queue->barber_id === $barber?->getKey(), 403);
    }
}
