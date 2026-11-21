<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_only_admin_can_open_staff_page(): void
    {
        $this->get('/admin/staf')->assertRedirect(route('admin.login'));

        $kasir = User::factory()->create(['role' => UserRole::Kasir]);
        $this->actingAs($kasir)->get('/admin/staf')->assertForbidden();
        $this->actingAs($kasir)->post('/admin/staf', ['name' => 'X', 'password' => 'rahasia123', 'role' => 'admin'])->assertForbidden();

        $this->actingAs($this->admin())->get('/admin/staf')->assertOk();
    }

    public function test_admin_creates_staff_who_can_then_login_by_name(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/staf', ['name' => 'Dewi Kasir', 'password' => 'rahasia123', 'role' => 'kasir'])
            ->assertRedirect();

        $user = User::where('name', 'Dewi Kasir')->firstOrFail();
        $this->assertSame(UserRole::Kasir, $user->role);

        $this->post('/admin/logout');
        $this->post('/admin/login', ['name' => 'Dewi Kasir', 'password' => 'rahasia123'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_duplicate_name_weak_password_and_invalid_role_are_rejected(): void
    {
        $admin = $this->admin();
        User::factory()->create(['name' => 'Dewi Kasir']);

        $this->actingAs($admin)->post('/admin/staf', ['name' => 'Dewi Kasir', 'password' => 'rahasia123', 'role' => 'kasir'])
            ->assertSessionHasErrors('name');
        $this->actingAs($admin)->post('/admin/staf', ['name' => 'Budi', 'password' => 'pendek', 'role' => 'kasir'])
            ->assertSessionHasErrors('password');
        $this->actingAs($admin)->post('/admin/staf', ['name' => 'Budi', 'password' => 'rahasia123', 'role' => 'superadmin'])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['name' => 'Budi']);
    }

    public function test_admin_can_reset_password(): void
    {
        $kasir = User::factory()->create(['name' => 'Dewi Kasir', 'password' => 'sandilama123', 'role' => UserRole::Kasir]);

        $this->actingAs($this->admin())->patch(route('admin.staff.update', $kasir), ['password' => 'sandibaru123'])
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('sandibaru123', $kasir->fresh()->password));
    }

    public function test_admin_can_delete_others_but_not_self(): void
    {
        $admin = $this->admin();
        $kasir = User::factory()->create(['role' => UserRole::Kasir]);

        $this->actingAs($admin)->delete(route('admin.staff.destroy', $kasir))->assertSessionHas('success');
        $this->assertModelMissing($kasir);

        $this->actingAs($admin)->delete(route('admin.staff.destroy', $admin))->assertSessionHas('error');
        $this->assertModelExists($admin);
    }

    public function test_seeder_is_safe_to_run_twice_and_keeps_changed_passwords(): void
    {
        $this->seed(UserSeeder::class);
        $admin = User::where('name', 'Admin Kedai')->firstOrFail();
        $admin->update(['password' => 'sandiyangdiganti1']);

        $this->seed(UserSeeder::class);

        $this->assertSame(2, User::count());
        $this->assertTrue(Hash::check('sandiyangdiganti1', $admin->fresh()->password));
    }

    public function test_customer_pages_do_not_show_staff_login_link(): void
    {
        $this->withoutVite();

        $this->get('/')->assertOk()->assertDontSee('Masuk staf');
    }
}
