<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminRoleTest extends TestCase
{
    use RefreshDatabase;

    private function loginAs(AdminRole $role): Admin
    {
        $admin = Admin::create([
            'email' => $role->value . '@admin.com',
            'password' => Hash::make('password123'),
            'role' => $role->value,
        ]);

        session([
            'isAdmin' => true,
            'adminId' => $admin->id,
            'adminEmail' => $admin->email,
            'adminRole' => $admin->role->value,
        ]);

        return $admin;
    }

    public function test_super_admin_can_access_pricing(): void
    {
        $this->loginAs(AdminRole::SuperAdmin);

        $response = $this->get('/admin/pricing');

        $response->assertOk();
    }

    public function test_finance_manager_can_access_pricing(): void
    {
        $this->loginAs(AdminRole::FinanceManager);

        $response = $this->get('/admin/pricing');

        $response->assertOk();
    }

    public function test_support_agent_cannot_access_pricing(): void
    {
        $this->loginAs(AdminRole::SupportAgent);

        $response = $this->get('/admin/pricing');

        $response->assertStatus(403);
    }

    public function test_only_super_admin_can_access_admin_user_management(): void
    {
        $this->loginAs(AdminRole::FinanceManager);

        $response = $this->get('/admin/users');

        $response->assertStatus(403);
    }

    public function test_super_admin_can_create_an_admin_with_a_role(): void
    {
        $this->loginAs(AdminRole::SuperAdmin);

        $response = $this->post('/admin/users', [
            'email' => 'newstaff@admin.com',
            'password' => 'a-strong-password',
            'password_confirmation' => 'a-strong-password',
            'role' => AdminRole::SupportAgent->value,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('admins', ['email' => 'newstaff@admin.com', 'role' => AdminRole::SupportAgent->value]);
    }

    public function test_super_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->loginAs(AdminRole::SuperAdmin);

        $response = $this->delete("/admin/users/{$admin->id}");

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseHas('admins', ['id' => $admin->id]);
    }

    /**
     * Reaching this controller at all requires the actor to already be a super
     * admin (route middleware), so demoting/deleting a *different* super admin
     * always leaves the actor as at least one remaining — proving the "last
     * super admin" guard doesn't over-block legitimate admin management.
     */
    public function test_a_super_admin_can_demote_or_delete_a_different_super_admin(): void
    {
        $this->loginAs(AdminRole::SuperAdmin);
        $other = Admin::create(['email' => 'other-super@admin.com', 'password' => Hash::make('password123'), 'role' => AdminRole::SuperAdmin->value]);

        $this->put("/admin/users/{$other->id}", ['role' => AdminRole::SupportAgent->value])
            ->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('admins', ['id' => $other->id, 'role' => AdminRole::SupportAgent->value]);
    }

    public function test_super_admin_cannot_demote_their_own_role(): void
    {
        $admin = $this->loginAs(AdminRole::SuperAdmin);

        $response = $this->put("/admin/users/{$admin->id}", ['role' => AdminRole::SupportAgent->value]);

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseHas('admins', ['id' => $admin->id, 'role' => AdminRole::SuperAdmin->value]);
    }
}
