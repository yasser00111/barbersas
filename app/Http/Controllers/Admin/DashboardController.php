<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Models\Barber;
use App\Models\Queue;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $today = now()->toDateString();

        $statusCounts = Queue::query()
            ->where('queue_date', $today)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'today_total' => Queue::where('queue_date', $today)->count(),
                'today_waiting' => (int) ($statusCounts[QueueStatus::Waiting->value] ?? 0),
                'today_in_service' => (int) ($statusCounts[QueueStatus::InService->value] ?? 0),
                'today_completed' => (int) ($statusCounts[QueueStatus::Completed->value] ?? 0),
                'today_cancelled' => (int) (($statusCounts[QueueStatus::Cancelled->value] ?? 0) + ($statusCounts[QueueStatus::NoShow->value] ?? 0)),
                'active_barbers' => Barber::where('is_active', true)->count(),
                'active_services' => Service::where('is_active', true)->count(),
                'revenue_today' => $this->revenue($today, $today),
                'revenue_week' => $this->revenue(now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()),
                'revenue_month' => $this->revenue(now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()),
            ],
            'nowServing' => Queue::with(['service', 'barber'])
                ->where('queue_date', $today)
                ->whereIn('status', [QueueStatus::Called->value, QueueStatus::InService->value])
                ->orderBy('queue_number')
                ->get()
                ->map(fn (Queue $queue) => $this->queueRow($queue)),
            'waitingList' => Queue::with(['service', 'barber'])
                ->where('queue_date', $today)
                ->where('status', QueueStatus::Waiting->value)
                ->orderBy('queue_number')
                ->limit(10)
                ->get()
                ->map(fn (Queue $queue) => $this->queueRow($queue)),
        ]);
    }

    protected function revenue(string $from, string $to): float
    {
        return (float) Queue::query()
            ->where('status', QueueStatus::Completed->value)
            ->whereBetween('queue_date', [$from, $to])
            ->join('services', 'services.id', '=', 'queues.service_id')
            ->sum('services.price');
    }

    /**
     * @return array<string, mixed>
     */
    protected function queueRow(Queue $queue): array
    {
        return [
            'id' => $queue->id,
            'number' => $queue->queue_number,
            'status' => $queue->status->value,
            'status_label' => $queue->status->label(),
            'customer' => $queue->customer?->name,
            'phone' => $queue->customer?->phone,
            'service' => $queue->service?->name,
            'barber' => $queue->barber?->name,
        ];
    }
}
