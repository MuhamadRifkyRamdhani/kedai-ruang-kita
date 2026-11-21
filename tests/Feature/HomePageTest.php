<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pengganti ExampleTest bawaan Laravel (yang tidak memakai database test dan butuh hasil build Vite). */
class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_lists_available_menu(): void
    {
        $this->withoutVite();
        Product::create(['name' => 'Es Kopi Ruang', 'category' => 'Minuman', 'price' => 22000, 'available' => true]);
        Product::create(['name' => 'Matcha Latte', 'category' => 'Minuman', 'price' => 25000, 'available' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Es Kopi Ruang')
            ->assertDontSee('Matcha Latte');
    }

    public function test_admin_pages_render_for_logged_in_staff(): void
    {
        $user = \App\Models\User::factory()->create(['role' => \App\Enums\UserRole::Admin]);

        foreach (['/admin', '/admin/pesanan', '/admin/pesanan/riwayat', '/admin/menu', '/admin/meja', '/admin/kanal'] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }
}
