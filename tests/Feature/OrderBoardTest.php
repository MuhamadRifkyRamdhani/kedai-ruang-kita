<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderBoardTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::factory()->create(['role' => UserRole::Kasir]);
    }

    private function makeOrder(string $status = 'baru'): Order
    {
        $order = Order::create([
            'code' => 'RK-T-' . Str::upper(Str::random(5)),
            'source' => 'Meja',
            'total' => 22000,
            'status' => $status,
        ]);
        $order->items()->create(['product_name' => 'Es Kopi Ruang', 'quantity' => 1, 'unit_price' => 22000, 'subtotal' => 22000]);

        return $order;
    }

    public function test_board_shows_active_orders(): void
    {
        $order = $this->makeOrder();

        $this->actingAs($this->staff())->get('/admin/pesanan')->assertOk()->assertSee($order->code);
    }

    public function test_status_moves_forward_and_is_logged_with_the_user(): void
    {
        $staff = $this->staff();
        $order = $this->makeOrder();

        $this->actingAs($staff)->patch(route('admin.orders.update', $order), ['status' => 'diproses'])
            ->assertSessionHas('success');

        $this->assertSame('diproses', $order->fresh()->status);
        $this->assertDatabaseHas('order_status_logs', [
            'order_id' => $order->id,
            'user_id' => $staff->id,
            'from_status' => 'baru',
            'to_status' => 'diproses',
        ]);
    }

    public function test_skipping_a_stage_is_rejected(): void
    {
        $order = $this->makeOrder('baru');

        $this->actingAs($this->staff())->patch(route('admin.orders.update', $order), ['status' => 'selesai'])
            ->assertSessionHas('error');

        $this->assertSame('baru', $order->fresh()->status);
    }

    public function test_finished_order_cannot_be_moved_again(): void
    {
        $order = $this->makeOrder('selesai');

        $this->actingAs($this->staff())->patch(route('admin.orders.update', $order), ['status' => 'dibatalkan'])
            ->assertSessionHas('error');

        $this->assertSame('selesai', $order->fresh()->status);
    }

    public function test_history_lists_only_finished_orders_and_supports_filter(): void
    {
        $done = $this->makeOrder('selesai');
        $active = $this->makeOrder('baru');

        $this->actingAs($this->staff())->get('/admin/pesanan/riwayat')
            ->assertOk()->assertSee($done->code)->assertDontSee($active->code);

        $this->actingAs($this->staff())->get('/admin/pesanan/riwayat?status=dibatalkan')
            ->assertOk()->assertDontSee($done->code);
    }

    public function test_manual_marketplace_order_stores_platform_id_and_blocks_duplicates(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff)->post('/admin/kanal/pesanan', ['source' => 'GoFood', 'total' => 45000, 'code' => 'GF-123456'])
            ->assertRedirect(route('admin.orders'));

        $order = Order::firstOrFail();
        $this->assertSame('GoFood', $order->source);
        $this->assertSame('GF-123456', $order->platform_order_id);
        $this->assertStringStartsWith('RK-', $order->code);
        $this->assertCount(1, $order->items);

        $this->actingAs($staff)->post('/admin/kanal/pesanan', ['source' => 'GoFood', 'total' => 45000, 'code' => 'GF-123456'])
            ->assertSessionHasErrors('code');

        $this->assertDatabaseCount('orders', 1);
    }
}
