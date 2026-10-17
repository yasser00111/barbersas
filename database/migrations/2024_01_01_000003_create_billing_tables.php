<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->string('currency', 3)->default('IDR');
            $table->unsignedInteger('duration_days')->default(30);
            $table->json('features')->nullable();
            $table->unsignedInteger('max_barbers')->nullable();
            $table->unsignedInteger('max_services')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->decimal('price', 15, 2)->default(0);
            $table->string('currency', 3)->default('IDR');
            $table->unsignedInteger('duration_days')->default(30);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number')->unique();
            $table->string('type', 20)->default('new');
            $table->string('status', 20)->default('unpaid')->index();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('IDR');
            $table->string('plan_name')->nullable();
            $table->unsignedInteger('duration_days')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('method', 20)->default('manual');
            $table->string('provider', 50)->nullable();
            $table->string('provider_reference')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('currency', 3)->default('IDR');
            $table->string('status', 20)->default('pending')->index();
            $table->string('proof_path')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->json('provider_payload')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->unique(['provider', 'provider_reference']);
        });

        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50);
            $table->string('event_id');
            $table->string('event_type')->nullable();
            $table->string('external_id')->nullable();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('signature_valid')->default(false);
            $table->string('status', 20)->default('received');
            $table->json('payload')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id']);
        });

        Schema::create('subscription_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->text('reason')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_events');
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
