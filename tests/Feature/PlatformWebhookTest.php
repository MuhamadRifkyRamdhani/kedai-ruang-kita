<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'rahasia-webhook-gofood-123';

    private function post_webhook(array $payload, ?string $secret = self::SECRET, string $platform = 'gofood')
    {
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, $secret ?? '');

        return $this->call('POST', "/api/webhooks/{$platform}", [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SIGNATURE' => $signature,
        ], $body);
    }

    public function test_unconfigured_platform_rejects_requests(): void
    {
        config(['ruangkita.channels.gofood.webhook_secret' => null]);

        $this->post_webhook(['order_id' => 'GF-1', 'total' => 1000])->assertStatus(503);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_invalid_signature_is_rejected_and_logged_without_payload(): void
    {
        config(['ruangkita.channels.gofood.webhook_secret' => self::SECRET]);

        $this->post_webhook(['order_id' => 'GF-1', 'total' => 45000], 'secret-salah')->assertStatus(401);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('webhook_events', ['platform' => 'gofood', 'signature_valid' => false, 'status' => 'failed']);
        $this->assertNull(WebhookEvent::first()->payload);
    }

    public function test_valid_webhook_creates_order_once_even_when_replayed(): void
    {
        config(['ruangkita.channels.gofood.webhook_secret' => self::SECRET]);
        $payload = ['order_id' => 'GF-777', 'total' => 45000, 'notes' => 'tanpa sambal'];

        $this->post_webhook($payload)->assertCreated();
        $this->post_webhook($payload)->assertOk(); // retry dari platform

        $this->assertDatabaseCount('orders', 1);
        $order = Order::firstOrFail();
        $this->assertSame('GoFood', $order->source);
        $this->assertSame('GF-777', $order->platform_order_id);
        $this->assertSame(45000, $order->total);
        $this->assertSame('baru', $order->status);

        $this->assertSame(['processed', 'duplicate'], WebhookEvent::orderBy('id')->pluck('status')->all());
    }

    public function test_item_details_are_stored_and_total_is_recomputed_from_items(): void
    {
        config(['ruangkita.channels.gofood.webhook_secret' => self::SECRET]);

        $this->post_webhook([
            'order_id' => 'GF-888',
            'total' => 1,
            'items' => [
                ['name' => 'Ayam Bakar Madu', 'quantity' => 2, 'price' => 35000],
                ['name' => 'Es Teh Nusantara', 'quantity' => 1, 'price' => 12000],
            ],
        ])->assertCreated();

        $order = Order::firstOrFail();
        $this->assertSame(82000, $order->total);
        $this->assertCount(2, $order->items);
    }

    public function test_signed_but_invalid_payload_is_rejected_and_logged(): void
    {
        config(['ruangkita.channels.gofood.webhook_secret' => self::SECRET]);

        $this->post_webhook(['total' => 45000])->assertStatus(422);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('webhook_events', ['platform' => 'gofood', 'signature_valid' => true, 'status' => 'failed']);
    }

    public function test_same_platform_order_id_on_different_platforms_is_allowed(): void
    {
        config([
            'ruangkita.channels.gofood.webhook_secret' => self::SECRET,
            'ruangkita.channels.grabfood.webhook_secret' => self::SECRET,
        ]);

        $this->post_webhook(['order_id' => 'X-1', 'total' => 10000], self::SECRET, 'gofood')->assertCreated();
        $this->post_webhook(['order_id' => 'X-1', 'total' => 10000], self::SECRET, 'grabfood')->assertCreated();

        $this->assertDatabaseCount('orders', 2);
    }

    public function test_unknown_platform_returns_404(): void
    {
        $this->post_webhook(['order_id' => 'X-1', 'total' => 10000], self::SECRET, 'tokopedia')->assertNotFound();
    }
}
