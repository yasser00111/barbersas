<?php

namespace Tests\Feature;

use App\Models\Barber;
use App\Models\Queue;
use App\Models\Service;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_scope_only_returns_current_tenant_records(): void
    {
        ['tenant' => $a] = $this->activeTenant();
        ['tenant' => $b] = $this->activeTenant();

        Service::factory()->count(2)->create(['tenant_id' => $a->id]);
        Service::factory()->count(3)->create(['tenant_id' => $b->id]);

        app(TenantContext::class)->set($a);
        $this->assertSame(2, Service::query()->count());

        app(TenantContext::class)->set($b);
        $this->assertSame(3, Service::query()->count());

        app(TenantContext::class)->forget();
        $this->assertSame(5, Service::withoutTenantScope()->count());
    }

    public function test_admin_cannot_update_another_tenants_service(): void
    {
        ['tenant' => $a, 'owner' => $ownerA] = $this->activeTenant();
        ['tenant' => $b] = $this->activeTenant();

        $serviceB = Service::factory()->create(['tenant_id' => $b->id, 'name' => 'Milik B']);

        $this->actingAs($ownerA)
            ->put("/admin/services/{$serviceB->id}", ['name' => 'Hacked', 'price' => 1, 'duration_minutes' => 10])
            ->assertNotFound();

        $this->assertDatabaseHas('services', ['id' => $serviceB->id, 'name' => 'Milik B']);
    }

    public function test_admin_cannot_call_another_tenants_queue(): void
    {
        ['tenant' => $a, 'owner' => $ownerA] = $this->activeTenant();
        ['tenant' => $b] = $this->activeTenant();

        $queueB = Queue::factory()->create(['tenant_id' => $b->id]);

        $this->actingAs($ownerA)
            ->post("/admin/queues/{$queueB->uuid}/call")
            ->assertNotFound();

        $this->assertSame('waiting', $queueB->fresh()->status->value);
    }

    public function test_admin_cannot_manage_another_tenants_barber(): void
    {
        ['tenant' => $a, 'owner' => $ownerA] = $this->activeTenant();
        ['tenant' => $b] = $this->activeTenant();

        $barberB = Barber::factory()->create(['tenant_id' => $b->id]);

        $this->actingAs($ownerA)
            ->delete("/admin/barbers/{$barberB->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('barbers', ['id' => $barberB->id]);
    }

    public function test_public_queue_status_is_scoped_to_the_tenant_in_url(): void
    {
        ['tenant' => $a] = $this->activeTenant();
        ['tenant' => $b] = $this->activeTenant();

        $queueA = Queue::factory()->create([
            'tenant_id' => $a->id,
            'queue_date' => now()->toDateString(),
            'queue_number' => 7,
        ]);

        $response = $this->get("/b/{$b->slug}/status/7");
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('queue', null));
    }
}
