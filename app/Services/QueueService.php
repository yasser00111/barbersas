<?php

namespace App\Services;

use App\Enums\QueueSource;
use App\Enums\QueueStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Barber;
use App\Models\BusinessHour;
use App\Models\Customer;
use App\Models\Queue;
use App\Models\QueueCounter;
use App\Models\Service;
use App\Models\Tenant;
use App\Support\TenantSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class QueueService
{
    public function __construct(protected AuditLogger $audit) {}

    /**
     * Validate whether a new queue may be taken right now.
     *
     * @return array{ok: bool, reason: string}
     */
    public function availability(Tenant $tenant, ?Service $service, ?Barber $barber, ?Carbon $at = null): array
    {
        $at = ($at ?? now())->copy()->setTimezone($tenant->timezone);

        if (! $this->tenantOperational($tenant)) {
            return $this->no('Barbershop sedang tidak menerima antrian saat ini.');
        }

        $settings = TenantSettings::for($tenant);

        if (! $settings->get('booking_enabled')) {
            return $this->no('Pengambilan antrian online sedang ditutup.');
        }

        if ($service && ! $service->is_active) {
            return $this->no('Layanan ini sedang tidak tersedia.');
        }

        if ($barber) {
            if (! $barber->is_active) {
                return $this->no('Barber yang dipilih sedang tidak tersedia.');
            }

            if ($service && ! $barber->canPerform($service)) {
                return $this->no('Barber yang dipilih tidak melayani layanan ini.');
            }
        }

        $hour = $tenant->businessHours()->where('day_of_week', $at->dayOfWeek)->first();

        if (! $hour || ! $hour->is_open) {
            return $this->no('Barbershop tutup hari ini.');
        }

        if (! $this->withinBusinessHours($hour, $at)) {
            return $this->no('Di luar jam operasional barbershop.');
        }

        $limit = (int) $settings->get('daily_queue_limit');

        if ($limit > 0 && $this->dailyCount($tenant, $at) >= $limit) {
            return $this->no('Kuota antrian hari ini sudah penuh.');
        }

        return ['ok' => true, 'reason' => ''];
    }

    public function tenantOperational(Tenant $tenant): bool
    {
        if (! $tenant->isActive()) {
            return false;
        }

        $subscription = $tenant->currentSubscription;

        return $subscription !== null
            && $subscription->status === SubscriptionStatus::Active
            && ($subscription->ends_at === null || $subscription->ends_at->isFuture());
    }

    /**
     * Allocate the next queue number for the tenant/date atomically.
     */
    public function nextNumber(Tenant $tenant, Carbon $date): int
    {
        return DB::transaction(function () use ($tenant, $date) {
            $day = $date->toDateString();

            $counter = QueueCounter::withoutTenantScope()
                ->where('tenant_id', $tenant->getKey())
                ->whereDate('queue_date', $day)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                try {
                    $counter = QueueCounter::withoutTenantScope()->create([
                        'tenant_id' => $tenant->getKey(),
                        'queue_date' => $day,
                        'last_number' => 0,
                    ]);
                } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                    $counter = QueueCounter::withoutTenantScope()
                        ->where('tenant_id', $tenant->getKey())
                        ->whereDate('queue_date', $day)
                        ->lockForUpdate()
                        ->firstOrFail();
                }
            }

            $counter->increment('last_number');

            return (int) $counter->last_number;
        });
    }

    public function create(
        Tenant $tenant,
        string $customerName,
        string $customerPhone,
        ?Service $service = null,
        ?Barber $barber = null,
        QueueSource $source = QueueSource::Online,
        ?string $email = null,
        ?Carbon $at = null,
    ): Queue {
        $at = ($at ?? now())->copy()->setTimezone($tenant->timezone);

        return DB::transaction(function () use ($tenant, $customerName, $customerPhone, $service, $barber, $source, $email, $at) {
            $customer = $this->upsertCustomer($tenant, $customerName, $customerPhone, $email);
            $number = $this->nextNumber($tenant, $at);

            $queue = Queue::create([
                'tenant_id' => $tenant->getKey(),
                'customer_id' => $customer->getKey(),
                'barber_id' => $barber?->getKey(),
                'service_id' => $service?->getKey(),
                'queue_date' => $at->toDateString(),
                'queue_number' => $number,
                'status' => QueueStatus::Waiting,
                'source' => $source,
            ]);

            $estimate = $this->estimateWaitMinutes($queue);

            $queue->forceFill([
                'estimated_wait_minutes' => $estimate,
                'estimated_start_at' => now()->addMinutes($estimate),
            ])->save();

            $this->audit->log(
                event: 'queue.created',
                auditable: $queue,
                description: 'Antrian #'.$number.' dibuat untuk '.$customerName,
                tenantId: $tenant->getKey(),
            );

            return $queue->fresh(['customer', 'service', 'barber']);
        });
    }

    public function estimateWaitMinutes(Queue $queue): int
    {
        $query = Queue::withoutTenantScope()
            ->where('tenant_id', $queue->tenant_id)
            ->where('queue_date', $queue->queue_date)
            ->where('queue_number', '<', $queue->queue_number)
            ->whereIn('status', [
                QueueStatus::Waiting->value,
                QueueStatus::Called->value,
                QueueStatus::InService->value,
            ]);

        if ($queue->barber_id !== null) {
            $query->where('barber_id', $queue->barber_id);
        }

        $ahead = $query->get();

        $minutes = 0;

        foreach ($ahead as $item) {
            $minutes += $item->service?->duration_minutes ?? 30;
        }

        $minutes += $queue->service?->duration_minutes ?? 30;

        return $minutes;
    }

    public function call(Queue $queue): void
    {
        $queue->forceFill([
            'status' => QueueStatus::Called,
            'called_at' => now(),
        ])->save();
    }

    public function recall(Queue $queue): void
    {
        $queue->forceFill([
            'status' => QueueStatus::Called,
            'called_at' => now(),
        ])->save();
    }

    public function start(Queue $queue, ?Barber $barber = null): void
    {
        $attributes = [
            'status' => QueueStatus::InService,
            'started_at' => now(),
        ];

        if ($barber) {
            $attributes['barber_id'] = $barber->getKey();
        }

        $queue->forceFill($attributes)->save();
    }

    public function complete(Queue $queue): void
    {
        DB::transaction(function () use ($queue) {
            $queue->forceFill([
                'status' => QueueStatus::Completed,
                'completed_at' => now(),
            ])->save();

            if ($queue->customer) {
                $queue->customer->increment('visits_count');
                $queue->customer->forceFill(['last_visit_at' => now()])->save();
            }
        });
    }

    public function cancel(Queue $queue, ?string $reason = null): void
    {
        $queue->forceFill([
            'status' => QueueStatus::Cancelled,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ])->save();
    }

    public function markNoShow(Queue $queue): void
    {
        $queue->forceFill(['status' => QueueStatus::NoShow])->save();
    }

    protected function upsertCustomer(Tenant $tenant, string $name, string $phone, ?string $email): Customer
    {
        $customer = Customer::withoutTenantScope()
            ->where('tenant_id', $tenant->getKey())
            ->where('phone', $phone)
            ->first();

        if ($customer) {
            $customer->forceFill([
                'name' => $name,
                'email' => $email ?? $customer->email,
            ])->save();

            return $customer;
        }

        return Customer::create([
            'tenant_id' => $tenant->getKey(),
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
        ]);
    }

    protected function dailyCount(Tenant $tenant, Carbon $date): int
    {
        return Queue::withoutTenantScope()
            ->where('tenant_id', $tenant->getKey())
            ->whereDate('queue_date', $date->toDateString())
            ->where('status', '!=', QueueStatus::Cancelled->value)
            ->count();
    }

    protected function withinBusinessHours(BusinessHour $hour, Carbon $at): bool
    {
        if (! $hour->open_time || ! $hour->close_time) {
            return false;
        }

        $time = $at->format('H:i:s');

        if ($time < $hour->open_time || $time > $hour->close_time) {
            return false;
        }

        if ($hour->break_start && $hour->break_end) {
            if ($time >= $hour->break_start && $time < $hour->break_end) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{ok: false, reason: string}
     */
    protected function no(string $reason): array
    {
        return ['ok' => false, 'reason' => $reason];
    }
}
