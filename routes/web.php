<?php

use App\Http\Controllers\Admin\BarberController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\QrCodeController;
use App\Http\Controllers\Admin\QueueController as AdminQueueController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredTenantController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Barber\DashboardController as BarberDashboardController;
use App\Http\Controllers\Public\BarbershopController;
use App\Http\Controllers\Public\LandingController;
use App\Http\Controllers\Public\PaymentWebhookController;
use App\Http\Controllers\Public\QueueController as PublicQueueController;
use App\Http\Controllers\SuperAdmin\AuditLogController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\InvoiceController as SuperAdminInvoiceController;
use App\Http\Controllers\SuperAdmin\PaymentController as SuperAdminPaymentController;
use App\Http\Controllers\SuperAdmin\SettingController as SuperAdminSettingController;
use App\Http\Controllers\SuperAdmin\SubscriptionEventController;
use App\Http\Controllers\SuperAdmin\SubscriptionPlanController;
use App\Http\Controllers\SuperAdmin\TenantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public marketing
|--------------------------------------------------------------------------
*/

Route::get('/', [LandingController::class, 'index'])->name('home');
Route::get('/pricing', [LandingController::class, 'pricing'])->name('pricing');

/*
|--------------------------------------------------------------------------
| Guest authentication
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredTenantController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredTenantController::class, 'store']);

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Email verification
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/verify-email', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

/*
|--------------------------------------------------------------------------
| Public barbershop (customer facing, no account required)
|--------------------------------------------------------------------------
*/

Route::prefix('b/{tenant}')->group(function () {
    Route::get('/', [BarbershopController::class, 'show'])->name('public.barbershop');

    Route::middleware('throttle:queue-creation')->group(function () {
        Route::get('/queue', [PublicQueueController::class, 'create'])->name('public.queue.create');
        Route::post('/queue', [PublicQueueController::class, 'store'])->name('public.queue.store');
    });

    Route::get('/status/{queueNumber}', [PublicQueueController::class, 'status'])->name('public.queue.status');
    Route::get('/status/{queueNumber}/poll', [PublicQueueController::class, 'poll'])->name('public.queue.poll');
    Route::post('/status/{queueNumber}/cancel', [PublicQueueController::class, 'cancel'])->name('public.queue.cancel');
});

/*
|--------------------------------------------------------------------------
| Payment gateway webhooks (signature authenticated)
|--------------------------------------------------------------------------
*/

Route::post('/webhooks/payments/{gateway}', [PaymentWebhookController::class, 'handle'])
    ->middleware('throttle:webhooks')
    ->name('webhooks.payments');

/*
|--------------------------------------------------------------------------
| Super Admin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'super_admin'])
    ->prefix('super-admin')
    ->name('super-admin.')
    ->group(function () {
        Route::get('/', [SuperAdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::get('/tenants/{tenant}', [TenantController::class, 'show'])->name('tenants.show');
        Route::post('/tenants/{tenant}/activate', [TenantController::class, 'activate'])->name('tenants.activate');
        Route::post('/tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('tenants.suspend');
        Route::post('/tenants/{tenant}/reactivate', [TenantController::class, 'reactivate'])->name('tenants.reactivate');
        Route::post('/tenants/{tenant}/archive', [TenantController::class, 'archive'])->name('tenants.archive');

        Route::get('/plans', [SubscriptionPlanController::class, 'index'])->name('plans.index');
        Route::post('/plans', [SubscriptionPlanController::class, 'store'])->name('plans.store');
        Route::put('/plans/{plan}', [SubscriptionPlanController::class, 'update'])->name('plans.update');
        Route::delete('/plans/{plan}', [SubscriptionPlanController::class, 'destroy'])->name('plans.destroy');

        Route::get('/invoices', [SuperAdminInvoiceController::class, 'index'])->name('invoices.index');

        Route::get('/payments', [SuperAdminPaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/{payment}/proof', [SuperAdminPaymentController::class, 'proof'])->name('payments.proof');
        Route::post('/payments/{payment}/approve', [SuperAdminPaymentController::class, 'approve'])->name('payments.approve');
        Route::post('/payments/{payment}/reject', [SuperAdminPaymentController::class, 'reject'])->name('payments.reject');

        Route::get('/subscriptions', [SubscriptionEventController::class, 'index'])->name('subscriptions.index');

        Route::get('/settings', [SuperAdminSettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SuperAdminSettingController::class, 'update'])->name('settings.update');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });

/*
|--------------------------------------------------------------------------
| Tenant Admin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'tenant_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Billing is always reachable so a tenant can renew after expiry.
        Route::get('/subscription', [AdminSubscriptionController::class, 'index'])->name('subscription.index');
        Route::post('/subscription/invoices/{invoice}/proof', [AdminSubscriptionController::class, 'uploadProof'])->name('subscription.proof');
        Route::post('/subscription/invoices/{invoice}/pay', [AdminSubscriptionController::class, 'pay'])->name('subscription.pay');
        Route::post('/subscription/renew', [AdminSubscriptionController::class, 'renew'])->name('subscription.renew');

        // Operational features require an active tenant + usable subscription.
        Route::middleware('tenant.active')->group(function () {
            Route::get('/barbers', [BarberController::class, 'index'])->name('barbers.index');
            Route::post('/barbers', [BarberController::class, 'store'])->name('barbers.store');
            Route::put('/barbers/{barber}', [BarberController::class, 'update'])->name('barbers.update');
            Route::delete('/barbers/{barber}', [BarberController::class, 'destroy'])->name('barbers.destroy');

            Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
            Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
            Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
            Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

            Route::get('/queues', [AdminQueueController::class, 'index'])->name('queues.index');
            Route::post('/queues', [AdminQueueController::class, 'store'])->name('queues.store');
            Route::post('/queues/call-next', [AdminQueueController::class, 'callNext'])->name('queues.call-next');
            Route::post('/queues/{queue}/call', [AdminQueueController::class, 'call'])->name('queues.call');
            Route::post('/queues/{queue}/recall', [AdminQueueController::class, 'recall'])->name('queues.recall');
            Route::post('/queues/{queue}/start', [AdminQueueController::class, 'start'])->name('queues.start');
            Route::post('/queues/{queue}/complete', [AdminQueueController::class, 'complete'])->name('queues.complete');
            Route::post('/queues/{queue}/cancel', [AdminQueueController::class, 'cancel'])->name('queues.cancel');
            Route::post('/queues/{queue}/no-show', [AdminQueueController::class, 'noShow'])->name('queues.no-show');

            Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
            Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

            Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

            Route::get('/qr-code', [QrCodeController::class, 'index'])->name('qr.index');
            Route::get('/qr-code/download', [QrCodeController::class, 'download'])->name('qr.download');

            Route::get('/settings', [AdminSettingsController::class, 'edit'])->name('settings.edit');
            Route::match(['put', 'post'], '/settings', [AdminSettingsController::class, 'update'])->name('settings.update');
        });
    });

/*
|--------------------------------------------------------------------------
| Barber
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])
    ->prefix('barber')
    ->name('barber.')
    ->group(function () {
        Route::get('/', [BarberDashboardController::class, 'index'])->name('dashboard');
        Route::post('/queues/{queue}/call', [BarberDashboardController::class, 'call'])->name('queues.call');
        Route::post('/queues/{queue}/start', [BarberDashboardController::class, 'start'])->name('queues.start');
        Route::post('/queues/{queue}/complete', [BarberDashboardController::class, 'complete'])->name('queues.complete');
    });
