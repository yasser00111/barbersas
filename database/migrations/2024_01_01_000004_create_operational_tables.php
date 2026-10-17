<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->boolean('is_open')->default(true);
            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();
            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'day_of_week']);
        });

        Schema::create('barbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('bio')->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->unsignedInteger('duration_minutes')->default(30);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('barber_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barber_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['barber_id', 'service_id']);
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('visits_count')->default(0);
            $table->timestamp('last_visit_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'phone']);
        });

        Schema::create('queue_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('queue_date');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'queue_date']);
        });

        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('barber_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->date('queue_date');
            $table->unsignedInteger('queue_number');
            $table->string('status', 20)->default('waiting')->index();
            $table->string('source', 20)->default('online');
            $table->unsignedInteger('estimated_wait_minutes')->nullable();
            $table->timestamp('estimated_start_at')->nullable();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'queue_date', 'queue_number']);
            $table->index(['tenant_id', 'queue_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queues');
        Schema::dropIfExists('queue_counters');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('barber_service');
        Schema::dropIfExists('services');
        Schema::dropIfExists('barbers');
        Schema::dropIfExists('business_hours');
    }
};
