<?php

namespace App\Http\Controllers\Public;

use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\QueueService;
use App\Support\TenantSettings;
use Inertia\Inertia;
use Inertia\Response;

class BarbershopController extends Controller
{
    public function __construct(protected QueueService $queues) {}

    public function show(Tenant $tenant): Response
    {
        if ($tenant->status !== TenantStatus::Active || ! $this->queues->tenantOperational($tenant)) {
            return Inertia::render('Public/Unavailable', [
                'tenant' => $this->tenantPayload($tenant),
                'reason' => $tenant->status === TenantStatus::Suspended ? 'suspended' : 'inactive',
            ]);
        }

        $tenant->load(['services' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'), 'barbers' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'), 'businessHours']);

        return Inertia::render('Public/Barbershop', [
            'tenant' => $this->tenantPayload($tenant),
            'services' => $tenant->services->map(fn ($service) => [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'price' => (float) $service->price,
                'duration_minutes' => $service->duration_minutes,
            ]),
            'barbers' => $tenant->barbers->map(fn ($barber) => [
                'id' => $barber->id,
                'name' => $barber->name,
                'bio' => $barber->bio,
                'photo_path' => $barber->photo_path,
                'service_ids' => $barber->services()->pluck('services.id'),
            ]),
            'businessHours' => $tenant->businessHours
                ->sortBy('day_of_week')
                ->map(fn ($hour) => [
                    'day_of_week' => $hour->day_of_week,
                    'day_name' => \App\Models\BusinessHour::dayName($hour->day_of_week),
                    'is_open' => $hour->is_open,
                    'open_time' => $hour->open_time,
                    'close_time' => $hour->close_time,
                ])->values(),
            'settings' => [
                'require_barber_selection' => (bool) TenantSettings::for($tenant)->get('require_barber_selection'),
                'tagline' => TenantSettings::for($tenant)->get('tagline'),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function tenantPayload(Tenant $tenant): array
    {
        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'logo_path' => $tenant->logo_path,
            'phone' => $tenant->phone,
            'address' => $tenant->address,
            'city' => $tenant->city,
        ];
    }
}
