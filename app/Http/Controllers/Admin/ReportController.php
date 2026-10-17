<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Models\Queue;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $from = $request->date('from')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $to = $request->date('to')?->toDateString() ?? now()->toDateString();

        $baseQuery = fn () => Queue::query()->whereBetween('queue_date', [$from, $to]);

        $statusCounts = $baseQuery()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $daily = $baseQuery()
            ->selectRaw('queue_date, count(*) as total')
            ->groupBy('queue_date')
            ->orderBy('queue_date')
            ->get()
            ->map(fn ($row) => [
                'date' => (string) $row->queue_date,
                'total' => (int) $row->total,
            ]);

        $revenueByService = Queue::query()
            ->whereBetween('queues.queue_date', [$from, $to])
            ->where('queues.status', QueueStatus::Completed->value)
            ->join('services', 'services.id', '=', 'queues.service_id')
            ->selectRaw('services.name as service, count(*) as transactions, sum(services.price) as revenue')
            ->groupBy('services.name')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row) => [
                'service' => $row->service,
                'transactions' => (int) $row->transactions,
                'revenue' => (float) $row->revenue,
            ]);

        $revenueByBarber = Queue::query()
            ->whereBetween('queues.queue_date', [$from, $to])
            ->where('queues.status', QueueStatus::Completed->value)
            ->leftJoin('barbers', 'barbers.id', '=', 'queues.barber_id')
            ->selectRaw('barbers.name as barber, count(*) as transactions, sum(services.price) as revenue')
            ->leftJoin('services', 'services.id', '=', 'queues.service_id')
            ->groupBy('barbers.name')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($row) => [
                'barber' => $row->barber ?: 'Tanpa Barber',
                'transactions' => (int) $row->transactions,
                'revenue' => (float) $row->revenue,
            ]);

        $totalRevenue = (float) Queue::query()
            ->whereBetween('queues.queue_date', [$from, $to])
            ->where('queues.status', QueueStatus::Completed->value)
            ->join('services', 'services.id', '=', 'queues.service_id')
            ->sum('services.price');

        return Inertia::render('Admin/Reports/Index', [
            'filters' => ['from' => $from, 'to' => $to],
            'summary' => [
                'total_queues' => (int) $baseQuery()->count(),
                'completed' => (int) ($statusCounts[QueueStatus::Completed->value] ?? 0),
                'cancelled' => (int) (($statusCounts[QueueStatus::Cancelled->value] ?? 0) + ($statusCounts[QueueStatus::NoShow->value] ?? 0)),
                'revenue' => $totalRevenue,
            ],
            'daily' => $daily,
            'revenueByService' => $revenueByService,
            'revenueByBarber' => $revenueByBarber,
        ]);
    }
}
