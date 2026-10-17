<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Public/Landing', [
            'plans' => $this->plans(),
        ]);
    }

    public function pricing(): Response
    {
        return Inertia::render('Public/Pricing', [
            'plans' => $this->plans(),
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    protected function plans()
    {
        return SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (SubscriptionPlan $plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price' => (float) $plan->price,
                'currency' => $plan->currency,
                'duration_days' => $plan->duration_days,
                'features' => $plan->features ?? [],
                'max_barbers' => $plan->max_barbers,
                'max_services' => $plan->max_services,
            ]);
    }
}
