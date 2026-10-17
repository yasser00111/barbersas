<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessHour;
use App\Services\AuditLogger;
use App\Support\TenantSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(protected AuditLogger $audit) {}

    public function edit(Request $request): Response
    {
        $tenant = $request->user()->tenant->load('businessHours');
        $settings = TenantSettings::for($tenant);

        return Inertia::render('Admin/Settings/Edit', [
            'profile' => [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'phone' => $tenant->phone,
                'address' => $tenant->address,
                'city' => $tenant->city,
                'logo_path' => $tenant->logo_path,
            ],
            'settings' => [
                'tagline' => $settings->get('tagline'),
                'booking_enabled' => (bool) $settings->get('booking_enabled'),
                'require_barber_selection' => (bool) $settings->get('require_barber_selection'),
                'allow_online_cancel' => (bool) $settings->get('allow_online_cancel'),
                'daily_queue_limit' => (int) $settings->get('daily_queue_limit'),
                'assignment_policy' => $settings->get('assignment_policy'),
                'payment_bank_name' => $settings->get('payment_bank_name'),
                'payment_bank_account' => $settings->get('payment_bank_account'),
                'payment_account_name' => $settings->get('payment_account_name'),
                'payment_instructions' => $settings->get('payment_instructions'),
            ],
            'businessHours' => $tenant->businessHours
                ->sortBy('day_of_week')
                ->values()
                ->map(fn (BusinessHour $hour) => [
                    'day_of_week' => $hour->day_of_week,
                    'day_name' => BusinessHour::dayName($hour->day_of_week),
                    'is_open' => $hour->is_open,
                    'open_time' => $hour->open_time ? substr($hour->open_time, 0, 5) : null,
                    'close_time' => $hour->close_time ? substr($hour->close_time, 0, 5) : null,
                    'break_start' => $hour->break_start ? substr($hour->break_start, 0, 5) : null,
                    'break_end' => $hour->break_end ? substr($hour->break_end, 0, 5) : null,
                ]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = $request->user()->tenant;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'max:2048'],

            'tagline' => ['nullable', 'string', 'max:160'],
            'booking_enabled' => ['boolean'],
            'require_barber_selection' => ['boolean'],
            'allow_online_cancel' => ['boolean'],
            'daily_queue_limit' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'assignment_policy' => ['nullable', 'in:least_busy,round_robin,manual'],
            'payment_bank_name' => ['nullable', 'string', 'max:120'],
            'payment_bank_account' => ['nullable', 'string', 'max:60'],
            'payment_account_name' => ['nullable', 'string', 'max:120'],
            'payment_instructions' => ['nullable', 'string', 'max:1000'],

            'business_hours' => ['array'],
            'business_hours.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'business_hours.*.is_open' => ['boolean'],
            'business_hours.*.open_time' => ['nullable', 'date_format:H:i'],
            'business_hours.*.close_time' => ['nullable', 'date_format:H:i'],
            'business_hours.*.break_start' => ['nullable', 'date_format:H:i'],
            'business_hours.*.break_end' => ['nullable', 'date_format:H:i'],
        ]);

        DB::transaction(function () use ($request, $tenant, $data) {
            $old = $tenant->only(['name', 'phone', 'address', 'city']);

            $tenant->fill($request->only(['name', 'phone', 'address', 'city']));

            if ($request->hasFile('logo')) {
                $tenant->logo_path = $request->file('logo')->store('tenant-logos/'.$tenant->getKey(), 'public');
            }

            $tenant->save();

            $settings = TenantSettings::for($tenant);
            foreach ([
                'tagline', 'booking_enabled', 'require_barber_selection', 'allow_online_cancel',
                'daily_queue_limit', 'assignment_policy', 'payment_bank_name', 'payment_bank_account',
                'payment_account_name', 'payment_instructions',
            ] as $key) {
                if (array_key_exists($key, $data)) {
                    $settings->set($key, $data[$key]);
                }
            }

            foreach ($request->input('business_hours', []) as $hour) {
                $tenant->businessHours()->updateOrCreate(
                    ['day_of_week' => (int) $hour['day_of_week']],
                    [
                        'is_open' => (bool) ($hour['is_open'] ?? false),
                        'open_time' => $hour['open_time'] ?? null,
                        'close_time' => $hour['close_time'] ?? null,
                        'break_start' => $hour['break_start'] ?? null,
                        'break_end' => $hour['break_end'] ?? null,
                    ],
                );
            }

            $this->audit->log('tenant.settings_updated', $tenant, 'Pengaturan barbershop diperbarui.', $old, $tenant->only(['name', 'phone', 'address', 'city']));
        });

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
