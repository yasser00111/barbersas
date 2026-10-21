<?php

namespace App\Support;

class PlatformConfigurator
{
    /**
     * Apply SMTP + payment-gateway settings stored by the super admin on top of
     * the environment defaults. Secrets remain encrypted in the database.
     */
    public function apply(): void
    {
        $this->applyMail();
        $this->applyPayment();
    }

    protected function applyMail(): void
    {
        $host = PlatformConfig::get('smtp_host');

        if (! $host) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => (int) PlatformConfig::get('smtp_port', 587),
            'mail.mailers.smtp.username' => PlatformConfig::get('smtp_username'),
            'mail.mailers.smtp.password' => PlatformConfig::get('smtp_password'),
            'mail.mailers.smtp.encryption' => PlatformConfig::get('smtp_encryption') ?: null,
        ]);

        if ($from = PlatformConfig::get('mail_from_address')) {
            config([
                'mail.from.address' => $from,
                'mail.from.name' => PlatformConfig::get('mail_from_name', config('app.name')),
            ]);
        }
    }

    protected function applyPayment(): void
    {
        $gateway = PlatformConfig::get('payment_gateway');

        if ($gateway) {
            config(['barbersaas.payment.default' => $gateway]);
        }

        $serverKey = PlatformConfig::get('midtrans_server_key');

        if ($serverKey) {
            config([
                'barbersaas.payment.gateways.midtrans.server_key' => $serverKey,
                'barbersaas.payment.gateways.midtrans.client_key' => PlatformConfig::get('midtrans_client_key', ''),
                'barbersaas.payment.gateways.midtrans.merchant_id' => PlatformConfig::get('midtrans_merchant_id', ''),
                'barbersaas.payment.gateways.midtrans.is_production' => (bool) PlatformConfig::get('midtrans_is_production', false),
            ]);
        }

        $duitkuApiKey = PlatformConfig::get('duitku_api_key');

        if ($duitkuApiKey || PlatformConfig::get('duitku_merchant_code')) {
            config([
                'barbersaas.payment.gateways.duitku.merchant_code' => PlatformConfig::get('duitku_merchant_code', ''),
                'barbersaas.payment.gateways.duitku.api_key' => $duitkuApiKey,
                'barbersaas.payment.gateways.duitku.is_production' => (bool) PlatformConfig::get('duitku_is_production', false),
            ]);
        }
    }
}
