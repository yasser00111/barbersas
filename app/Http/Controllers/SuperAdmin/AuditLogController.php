<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SubscriptionEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $logs = AuditLog::query()
            ->with(['user', 'tenant'])
            ->when($request->filled('event'), fn ($q) => $q->where('event', 'like', '%'.$request->string('event').'%'))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('SuperAdmin/AuditLogs/Index', [
            'logs' => $logs->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'event' => $log->event,
                'description' => $log->description,
                'user' => $log->user?->name,
                'tenant' => $log->tenant?->name,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->toIso8601String(),
                'changes' => [
                    'old' => $log->old_values,
                    'new' => $log->new_values,
                ],
            ]),
            'filters' => ['event' => $request->string('event')->toString()],
        ]);
    }
}
