<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\SubscriptionPlanRequest;
use App\Models\SubscriptionPlan;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionPlanController extends Controller
{
    public function __construct(protected AuditLogger $audit) {}

    public function index(): Response
    {
        return Inertia::render('SuperAdmin/Plans/Index', [
            'plans' => SubscriptionPlan::query()->orderBy('sort_order')->get()->map(fn (SubscriptionPlan $plan) => [
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
                'is_active' => $plan->is_active,
            ]),
        ]);
    }

    public function store(SubscriptionPlanRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? $data['name']);

        $plan = SubscriptionPlan::create($data);

        $this->audit->log('plan.created', $plan, 'Paket langganan dibuat: '.$plan->name);

        return back()->with('success', 'Paket langganan dibuat.');
    }

    public function update(SubscriptionPlanRequest $request, SubscriptionPlan $plan): RedirectResponse
    {
        $data = $request->validated();

        if (isset($data['slug']) && $data['slug'] !== $plan->slug) {
            $data['slug'] = $this->uniqueSlug($data['slug'], $plan->getKey());
        }

        $old = ['price' => (float) $plan->price, 'duration_days' => $plan->duration_days, 'is_active' => $plan->is_active];

        $plan->update($data);

        $this->audit->log('plan.updated', $plan, 'Paket langganan diperbarui: '.$plan->name, $old, [
            'price' => (float) $plan->price,
            'duration_days' => $plan->duration_days,
            'is_active' => $plan->is_active,
        ]);

        return back()->with('success', 'Paket langganan diperbarui. Invoice lama tidak berubah.');
    }

    public function destroy(SubscriptionPlan $plan): RedirectResponse
    {
        if ($plan->subscriptions()->exists()) {
            $plan->update(['is_active' => false]);

            return back()->with('success', 'Paket dinonaktifkan karena sudah dipakai pada langganan.');
        }

        $plan->delete();

        $this->audit->log('plan.deleted', null, 'Paket langganan dihapus: '.$plan->name);

        return back()->with('success', 'Paket langganan dihapus.');
    }

    protected function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'plan';
        $slug = $base;
        $i = 1;

        while (SubscriptionPlan::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
