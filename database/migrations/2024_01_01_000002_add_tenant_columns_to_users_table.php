<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('role', 30)->default('tenant_admin')->index()->after('password');
            $table->string('status', 20)->default('active')->after('role');
            $table->foreignId('tenant_id')->nullable()->after('status')
                ->constrained('tenants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn(['phone', 'role', 'status']);
        });
    }
};
