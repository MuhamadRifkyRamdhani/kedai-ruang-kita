<?php

namespace App\Services;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class MarketplaceOrderService
{
    public function __construct(private OrderService $orders)
    {
    }

    /**
     * Catat pesanan GoFood/GrabFood/ShopeeFood (input manual kasir atau webhook).
     * Idempoten: ID platform yang sama untuk platform yang sama tidak dibuat dua kali.
     * Cek lewat $order->wasRecentlyCreated untuk tahu apakah pesanan baru atau sudah ada.
     *
     * @param array<int, array{name:string, quantity:int, price:int}> $items kosong = satu baris ringkasan "Pesanan {platform}"
     */
    public function record(
        OrderSource $source,
        int $total,
        ?string $platformOrderId = null,
        ?string $notes = null,
        array $items = [],
    ): Order {
        if ($platformOrderId !== null && ($existing = $this->find($source, $platformOrderId))) {
            return $existing;
        }

        try {
            return DB::transaction(fn () => $this->create($source, $total, $platformOrderId, $notes, $items));
        } catch (UniqueConstraintViolationException $e) {
            // Dua request dengan ID yang sama masuk bersamaan; yang kalah mengambil pesanan yang menang.
            if ($platformOrderId !== null && ($existing = $this->find($source, $platformOrderId))) {
                return $existing;
            }

            throw $e;
        }
    }

    private function find(OrderSource $source, string $platformOrderId): ?Order
    {
        return Order::where('source', $source->value)
            ->where('platform_order_id', $platformOrderId)
            ->first();
    }

    private function create(OrderSource $source, int $total, ?string $platformOrderId, ?string $notes, array $items): Order
    {
        $lines = collect($items)->map(fn ($item) => [
            'product_id' => null,
            'product_name' => $item['name'],
            'quantity' => (int) $item['quantity'],
            'unit_price' => (int) $item['price'],
            'subtotal' => (int) $item['price'] * (int) $item['quantity'],
        ]);

        if ($lines->isEmpty()) {
            $lines = collect([[
                'product_id' => null,
                'product_name' => 'Pesanan ' . $source->value,
                'quantity' => 1,
                'unit_price' => $total,
                'subtotal' => $total,
            ]]);
        } else {
            $total = (int) $lines->sum('subtotal');
        }

        $order = Order::create([
            'code' => $this->orders->generateCode(),
            'platform_order_id' => $platformOrderId,
            'source' => $source->value,
            'notes' => $notes,
            'total' => $total,
            'status' => OrderStatus::Baru->value,
        ]);

        $order->items()->createMany($lines->all());
        $order->statusLogs()->create(['to_status' => OrderStatus::Baru->value]);

        return $order->load('items');
    }
}
