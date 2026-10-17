<?php

namespace App\Providers;

use App\Models\Barber;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Queue;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Payments\PaymentGatewayManager;
use App\Policies\InvoicePolicy;
use App\Policies\PaymentPolicy;
use App\Policies\SubscriptionPlanPolicy;
use App\Policies\TenantPolicy;
use App\Policies\UserPolicy;
use App\Support\PlatformConfigurator;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class, fn () => new TenantContext());

        $this->app->singleton(PaymentGatewayManager::class, function ($app) {
            $config = $app['config']->get('barbersaas.payment', []);

            return new PaymentGatewayManager(
                $config['gateways'] ?? [],
                $config['default'] ?? 'manual',
            );
        });
    }

    public function boot(): void
    {
        try {
            $this->app->make(PlatformConfigurator::class)->apply();
        } catch (\Throwable) {
            // Ignore if database/tables are not ready yet
        }

        Relation::enforceMorphMap([
            'tenant' => Tenant::class,
            'user' => User::class,
            'subscription' => Subscription::class,
            'subscription_plan' => SubscriptionPlan::class,
            'invoice' => Invoice::class,
            'payment' => Payment::class,
            'service' => Service::class,
            'barber' => Barber::class,
            'customer' => Customer::class,
            'queue' => Queue::class,
        ]);

        Gate::policy(Tenant::class, TenantPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(SubscriptionPlan::class, SubscriptionPlanPolicy::class);

        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $key = strtolower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinute(5)->by($key);
        });

        RateLimiter::for('queue-creation', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });
    }
}
