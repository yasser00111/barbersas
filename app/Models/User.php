<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'tenant_id',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'status' => UserStatus::class,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(TenantMember::class);
    }

    public function barber(): HasOne
    {
        return $this->hasOne(Barber::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === Role::SuperAdmin;
    }

    public function isTenantAdmin(): bool
    {
        return $this->role === Role::TenantAdmin;
    }

    public function isBarber(): bool
    {
        return $this->role === Role::Barber;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function belongsToTenant(int $tenantId): bool
    {
        return (int) $this->tenant_id === $tenantId;
    }
}
