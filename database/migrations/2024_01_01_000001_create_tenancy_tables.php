<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('timezone')->default('Asia/Jakarta');
            $table->string('status', 20)->default('pending')->index();
            $table->text('suspended_reason')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tenant_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 30)->default('staff');
            $table->string('status', 20)->default('active');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id']);
        });

        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'key']);
        });

        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('tenant_settings');
        Schema::dropIfExists('tenant_members');
        Schema::dropIfExists('tenants');
    }
};
