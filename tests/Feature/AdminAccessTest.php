<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(UserRole $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function makeOrder(string $status, int $total): Order
    {
        return Order::create([
            'code' => 'RK-T-' . Str::upper(Str::random(5)),
            'source' => 'Meja',
            'total' => $total,
            'status' => $status,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/pesanan')->assertRedirect(route('admin.login'));
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_staff_can_login_with_correct_password(): void
    {
        User::factory()->create(['name' => 'Admin Test', 'password' => 'rahasia123', 'role' => UserRole::Admin]);

        $this->post('/admin/login', ['name' => 'Admin Test', 'password' => 'rahasia123'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->create(['name' => 'Admin Test', 'password' => 'rahasia123', 'role' => UserRole::Admin]);

        $this->post('/admin/login', ['name' => 'Admin Test', 'password' => 'salah'])
            ->assertSessionHasErrors('name');

        $this->assertGuest();
    }

    public function test_kasir_cannot_create_menu_but_admin_can(): void
    {
        $payload = ['name' => 'Tahu Crispy', 'category' => 'Camilan', 'price' => 15000];

        $this->actingAs($this->user(UserRole::Kasir))->post('/admin/menu', $payload)->assertForbidden();
        $this->assertDatabaseMissing('products', ['name' => 'Tahu Crispy']);

        $this->actingAs($this->user(UserRole::Admin))->post('/admin/menu', $payload)->assertRedirect();
        $this->assertDatabaseHas('products', ['name' => 'Tahu Crispy']);
    }

    public function test_kasir_can_toggle_menu_availability(): void
    {
        $product = Product::create(['name' => 'Soto Ayam', 'category' => 'Makanan Utama', 'price' => 27000, 'available' => true]);

        $this->actingAs($this->user(UserRole::Kasir))->patch(route('admin.menu.stock', $product))->assertRedirect();

        $this->assertFalse($product->fresh()->available);
    }

    public function test_admin_can_add_table_and_kasir_cannot(): void
    {
        $this->actingAs($this->user(UserRole::Kasir))->post('/admin/meja', ['number' => 13])->assertForbidden();

        $this->actingAs($this->user(UserRole::Admin))->post('/admin/meja', ['number' => 13])->assertRedirect();
        $this->assertDatabaseHas('restaurant_tables', ['number' => 13]);

        $this->post('/admin/meja', ['number' => 13])->assertSessionHasErrors('number');
    }

    public function test_dashboard_revenue_counts_only_ready_and_completed_orders(): void
    {
        $this->makeOrder('baru', 10000);
        $this->makeOrder('siap', 10000);
        $this->makeOrder('selesai', 20000);
        $this->makeOrder('dibatalkan', 5000);

        $this->actingAs($this->user(UserRole::Admin))
            ->get('/admin?periode=hari')
            ->assertOk()
            ->assertViewHas('revenue', 30000)
            ->assertViewHas('count', 2)
            ->assertViewHas('pending', 2);
    }

    public function test_tables_page_lists_tables_from_database(): void
    {
        RestaurantTable::create(['number' => 1]);
        RestaurantTable::create(['number' => 2, 'is_active' => false]);

        $this->actingAs($this->user(UserRole::Kasir))
            ->get('/admin/meja')
            ->assertOk()
            ->assertViewHas('tables', fn ($tables) => $tables->all() === [1]);
    }
}
