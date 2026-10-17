<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_loads(): void
    {
        SubscriptionPlan::factory()->count(2)->create(['is_active' => true]);

        $this->get('/')->assertOk();
    }

    public function test_pricing_page_loads(): void
    {
        SubscriptionPlan::factory()->count(2)->create(['is_active' => true]);

        $this->get('/pricing')->assertOk();
    }
}
