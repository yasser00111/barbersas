<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
        $this->get('/super-admin')->assertRedirect(route('login'));
        $this->get('/barber')->assertRedirect(route('login'));
    }

    public function test_super_admin_can_access_platform_area(): void
    {
        $this->actingAs($this->superAdmin())->get('/super-admin')->assertOk();
    }

    public function test_super_admin_is_forbidden_from_tenant_admin_area(): void
    {
        $this->actingAs($this->superAdmin())->get('/admin')->assertForbidden();
    }

    public function test_tenant_admin_can_access_admin_area(): void
    {
        ['owner' => $owner] = $this->activeTenant();

        $this->actingAs($owner)->get('/admin')->assertOk();
    }

    public function test_tenant_admin_is_forbidden_from_platform_area(): void
    {
        ['owner' => $owner] = $this->activeTenant();

        $this->actingAs($owner)->get('/super-admin')->assertForbidden();
    }

    public function test_barber_is_forbidden_from_admin_area(): void
    {
        ['tenant' => $tenant] = $this->activeTenant();
        ['user' => $barber] = $this->barberFor($tenant);

        $this->actingAs($barber)->get('/admin')->assertForbidden();
    }

    public function test_barber_can_access_barber_console(): void
    {
        ['tenant' => $tenant] = $this->activeTenant();
        ['user' => $barber] = $this->barberFor($tenant);

        $this->actingAs($barber)->get('/barber')->assertOk();
    }

    public function test_suspended_user_is_logged_out_and_redirected(): void
    {
        $user = $this->superAdmin(['status' => UserStatus::Suspended]);

        $this->actingAs($user)
            ->get('/super-admin')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
