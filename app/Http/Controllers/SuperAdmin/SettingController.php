<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Support\PlatformConfig;
use App\Support\PlatformConfigurator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function __construct(
        protected AuditLogger $audit,
        protected PlatformConfigurator $configurator,
    ) {}

    public function edit(): Response
    {
        return Inertia::render('SuperAdmin/Settings/Edit', [
            'settings' => [
                'smtp_host' => PlatformConfig::get('smtp_host', config('mail.mailers.smtp.host')),
                'smtp_port' => PlatformConfig::get('smtp_port', config('mail.mailers.smtp.port')),
                'smtp_username' => PlatformConfig::get('smtp_username'),
                'smtp_encryption' => PlatformConfig::get('smtp_encryption'),
                'smtp_password_set' => (bool) PlatformConfig::get('smtp_password'),
                'mail_from_address' => PlatformConfig::get('mail_from_address', config('mail.from.address')),
                'mail_from_name' => PlatformConfig::get('mail_from_name', config('mail.from.name')),
                'payment_gateway' => PlatformConfig::get('payment_gateway', config('barbersaas.payment.default')),
                'midtrans_server_key_set' => (bool) PlatformConfig::get('midtrans_server_key'),
                'midtrans_client_key' => PlatformConfig::get('midtrans_client_key'),
                'midtrans_merchant_id' => PlatformConfig::get('midtrans_merchant_id'),
                'midtrans_is_production' => (bool) PlatformConfig::get('midtrans_is_production', config('barbersaas.payment.gateways.midtrans.is_production')),
                'grace_period_days' => (int) PlatformConfig::get('grace_period_days', config('barbersaas.subscription.grace_days')),
            ],
            'gateways' => array_keys(config('barbersaas.payment.gateways', [])),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'smtp_host' => ['nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'smtp_encryption' => ['nullable', 'in:tls,ssl,starttls'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
            'payment_gateway' => ['required', 'string', 'in:manual,midtrans,fake'],
            'midtrans_server_key' => ['nullable', 'string', 'max:255'],
            'midtrans_client_key' => ['nullable', 'string', 'max:255'],
            'midtrans_merchant_id' => ['nullable', 'string', 'max:255'],
            'midtrans_is_production' => ['boolean'],
            'grace_period_days' => ['nullable', 'integer', 'min:0', 'max:90'],
        ]);

        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            $encrypted = in_array($key, ['smtp_password', 'midtrans_server_key'], true);
            PlatformConfig::set($key, $value, $encrypted);
        }

        $this->configurator->apply();

        $this->audit->log('platform.settings_updated', null, 'Pengaturan platform diperbarui.', [], [
            'payment_gateway' => $data['payment_gateway'] ?? null,
        ]);

        return back()->with('success', 'Pengaturan platform disimpan.');
    }
}
