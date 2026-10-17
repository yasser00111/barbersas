<?php

namespace Tests\Feature\Queue;

use App\Enums\QueueSource;
use App\Enums\QueueStatus;
use App\Models\Barber;
use App\Models\Service;
use App\Services\QueueService;
use App\Support\TenantSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_numbers_are_sequential_per_tenant_and_date(): void
    {
        ['tenant' => $tenant] = $this->activeTenant();
        $service = Service::factory()->create(['tenant_id' => $tenant->id]);

        $serviceLayer = app(QueueService::class);

        $first = $serviceLayer->create($tenant, 'A', '0811', $service, source: QueueSource::Online);
        $second = $serviceLayer->create($tenant, 'B', '0812', $service, source: QueueSource::Online);

        $this->assertSame(1, $first->queue_number);
        $this->assertSame(2, $second->queue_number);
    }

    public function test_queue_numbering_is_isolated_between_tenants(): void
    {
        ['tenant' => $a] = $this->activeTenant();
        ['tenant' => $b] = $this->activeTenant();

        $serviceA = Service::factory()->create(['tenant_id' => $a->id]);
        $serviceB = Service::factory()->create(['tenant_id' => $b->id]);

        $serviceLayer = app(QueueService::class);

        $qa = $serviceLayer->create($a, 'A', '0811', $serviceA);
        $qb = $serviceLayer->create($b, 'B', '0812', $serviceB);

        $this->assertSame(1, $qa->queue_number);
        $this->assertSame(1, $qb->queue_number);
        $this->assertNotSame($qa->tenant_id, $qb->tenant_id);
    }

    public function test_public_queue_creation_through_endpoint(): void
    {
        ['tenant' => $tenant] = $this->activeTenant();
        $service = Service::factory()->create(['tenant_id' => $tenant->id, 'is_active' => true]);

        $response = $this->post("/b/{$tenant->slug}/queue", [
            'name' => 'Pelanggan Baru',
            'phone' => '081234567890',
            'service_id' => $service->id,
        ]);

        $this->assertDatabaseHas('queues', [
            'tenant_id' => $tenant->id,
            'queue_number' => 1,
            'status' => QueueStatus::Waiting->value,
        ]);

        $queue = $tenant->queues()->first();
        $response->assertRedirect(route('public.queue.status', [
            'tenant' => $tenant->slug,
            'queueNumber' => $queue->queue_number,
        ]));
    }

    public function test_queue_is_unavailable_when_shop_is_closed_today(): void
    {
        ['tenant' => $tenant] = $this->activeTenant();
        $service = Service::factory()->create(['tenant_id' => $tenant->id]);

        $tenant->businessHours()->where('day_of_week', now($tenant->timezone)->dayOfWeek)->update(['is_open' => false]);

        $availability = app(QueueService::class)->availability($tenant->fresh(), $service, null);

        $this->assertFalse($availability['ok']);
    }

    public function test_queue_is_unavailable_without_usable_subscription(): void
    {
        ['tenant' => $tenant, 'subscription' => $subscription] = $this->activeTenant();
        $service = Service::factory()->create(['tenant_id' => $tenant->id]);

        $subscription->forceFill(['ends_at' => now()->subDay()])->save();

        $availability = app(QueueService::class)->availability($tenant->fresh(), $service, null);

        $this->assertFalse($availability['ok']);
    }

    public function test_queue_is_unavailable_when_online_booking_disabled(): void
    {
        ['tenant' => $tenant] = $this->activeTenant();
        $service = Service::factory()->create(['tenant_id' => $tenant->id]);

        TenantSettings::for($tenant)->set('booking_enabled', false);

        $availability = app(QueueService::class)->availability($tenant->fresh(), $service, null);

        $this->assertFalse($availability['ok']);
    }

    public function test_admin_can_create_walk_in_queue(): void
    {
        ['tenant' => $tenant, 'owner' => $owner] = $this->activeTenant();
        $service = Service::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($owner)
            ->post('/admin/queues', [
                'name' => 'Walk In',
                'phone' => '081300000000',
                'service_id' => $service->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('queues', [
            'tenant_id' => $tenant->id,
            'source' => QueueSource::WalkIn->value,
        ]);
    }

    public function test_completing_queue_increments_customer_visits(): void
    {
        ['tenant' => $tenant] = $this->activeTenant();
        $service = Service::factory()->create(['tenant_id' => $tenant->id]);
        $serviceLayer = app(QueueService::class);

        $queue = $serviceLayer->create($tenant, 'Pelanggan', '0812', $service);

        $serviceLayer->call($queue);
        $this->assertSame(QueueStatus::Called, $queue->fresh()->status);

        $serviceLayer->start($queue->fresh());
        $this->assertSame(QueueStatus::InService, $queue->fresh()->status);

        $serviceLayer->complete($queue->fresh());
        $this->assertSame(QueueStatus::Completed, $queue->fresh()->status);

        $this->assertSame(1, $queue->customer->fresh()->visits_count);
        $this->assertNotNull($queue->customer->fresh()->last_visit_at);
    }

    public function test_estimated_wait_accumulates_service_durations_ahead(): void
    {
        ['tenant' => $tenant] = $this->activeTenant();
        $service = Service::factory()->create(['tenant_id' => $tenant->id, 'duration_minutes' => 30]);
        $serviceLayer = app(QueueService::class);

        $first = $serviceLayer->create($tenant, 'A', '0811', $service);
        $second = $serviceLayer->create($tenant, 'B', '0812', $service);

        $this->assertSame(30, $first->estimated_wait_minutes);
        $this->assertSame(60, $second->fresh()->estimated_wait_minutes);
    }

    public function test_barber_can_only_operate_on_own_or_unassigned_queues(): void
    {
        ['tenant' => $tenant] = $this->activeTenant();
        $otherBarber = Barber::factory()->create(['tenant_id' => $tenant->id]);
        ['user' => $barberUser, 'barber' => $barber] = $this->barberFor($tenant);

        $service = Service::factory()->create(['tenant_id' => $tenant->id]);

        $assignedToOther = \App\Models\Queue::factory()->create([
            'tenant_id' => $tenant->id,
            'barber_id' => $otherBarber->id,
            'service_id' => $service->id,
            'queue_date' => now()->toDateString(),
            'status' => QueueStatus::Waiting,
        ]);

        $this->actingAs($barberUser)
            ->post("/barber/queues/{$assignedToOther->uuid}/call")
            ->assertForbidden();

        $unassigned = \App\Models\Queue::factory()->create([
            'tenant_id' => $tenant->id,
            'barber_id' => null,
            'service_id' => $service->id,
            'queue_date' => now()->toDateString(),
            'status' => QueueStatus::Waiting,
        ]);

        $this->actingAs($barberUser)
            ->post("/barber/queues/{$unassigned->uuid}/call")
            ->assertRedirect();

        $this->assertSame(QueueStatus::Called, $unassigned->fresh()->status);
    }
}
