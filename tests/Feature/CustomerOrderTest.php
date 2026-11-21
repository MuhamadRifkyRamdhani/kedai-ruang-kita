<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\RestaurantTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $name, int $price, bool $available = true): Product
    {
        return Product::create([
            'name' => $name, 'category' => 'Minuman', 'price' => $price, 'available' => $available,
        ]);
    }

    public function test_total_is_computed_on_the_server_and_duplicate_lines_are_merged(): void
    {
        RestaurantTable::create(['number' => 4]);
        $kopi = $this->product('Es Kopi Ruang', 22000);
        $teh = $this->product('Es Teh Nusantara', 12000);

        $response = $this->post('/pesanan', [
            'table_number' => 4,
            'customer_name' => 'Dhito',
            'total' => 1, // harga dari browser harus diabaikan
            'items' => [
                ['id' => $kopi->id, 'quantity' => 1],
                ['id' => $teh->id, 'quantity' => 1],
                ['id' => $teh->id, 'quantity' => 1],
            ],
        ]);

        $order = Order::firstOrFail();
        $response->assertRedirect(route('orders.show', $order));
        $this->assertSame(46000, $order->total);
        $this->assertSame('baru', $order->status);
        $this->assertSame('Meja', $order->source);
        $this->assertSame(4, $order->table_number);
        $this->assertStringStartsWith('RK-', $order->code);
        $this->assertCount(2, $order->items);
        $this->assertCount(1, $order->statusLogs);
        $this->assertNotEmpty($order->public_token);
    }

    public function test_unavailable_menu_is_rejected(): void
    {
        $habis = $this->product('Matcha Latte', 25000, available: false);

        $this->post('/pesanan', ['items' => [['id' => $habis->id, 'quantity' => 1]]])
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_unknown_table_number_is_rejected(): void
    {
        $kopi = $this->product('Es Kopi Ruang', 22000);

        $this->post('/pesanan', [
            'table_number' => 50,
            'items' => [['id' => $kopi->id, 'quantity' => 1]],
        ])->assertSessionHasErrors('table_number');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_quantity_limit_is_enforced(): void
    {
        $kopi = $this->product('Es Kopi Ruang', 22000);

        $this->post('/pesanan', ['items' => [['id' => $kopi->id, 'quantity' => 21]]])
            ->assertSessionHasErrors('items.0.quantity');
    }

    public function test_confirmation_page_uses_random_token_not_sequential_id(): void
    {
        $this->withoutVite();
        $kopi = $this->product('Es Kopi Ruang', 22000);
        $this->post('/pesanan', ['items' => [['id' => $kopi->id, 'quantity' => 1]]]);
        $order = Order::firstOrFail();

        $this->get(route('orders.show', $order))->assertOk()->assertSee($order->code);
        $this->get('/pesanan/' . $order->id)->assertNotFound();
    }

    public function test_table_page_only_works_for_registered_active_tables(): void
    {
        $this->withoutVite();
        RestaurantTable::create(['number' => 2]);
        RestaurantTable::create(['number' => 3, 'is_active' => false]);

        $this->get('/meja/2')->assertOk();
        $this->get('/meja/3')->assertNotFound();
        $this->get('/meja/7')->assertNotFound();
    }
}
