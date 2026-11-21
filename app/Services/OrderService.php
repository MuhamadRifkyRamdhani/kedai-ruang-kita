<?php

namespace App\Services;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransition;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Simpan pesanan pelanggan (QR meja / katalog umum).
     * Harga selalu dari database; baris menu yang sama digabung.
     *
     * @param array{items: array<int, array{id:int|string, quantity:int|string}>, table_number?: int|string|null, customer_name?: string|null, notes?: string|null} $data
     */
    public function createFromCustomer(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $quantities = collect($data['items'])
                ->groupBy('id')
                ->map(fn ($rows) => (int) $rows->sum('quantity'));

            $products = Product::available()
                ->whereIn('id', $quantities->keys()->all())
                ->get()
                ->keyBy('id');

            if ($products->count() !== $quantities->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Ada menu yang sudah habis. Muat ulang halaman lalu pilih kembali.',
                ]);
            }

            $total = 0;
            $lines = [];
            foreach ($quantities as $productId => $quantity) {
                $product = $products[(int) $productId];
                $subtotal = $product->price * $quantity;
                $total += $subtotal;
                $lines[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'subtotal' => $subtotal,
                ];
            }

            $order = Order::create([
                'code' => $this->generateCode(),
                'source' => OrderSource::Meja->value,
                'table_number' => ! empty($data['table_number']) ? (int) $data['table_number'] : null,
                'customer_name' => $data['customer_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'total' => $total,
                'status' => OrderStatus::Baru->value,
            ]);

            $order->items()->createMany($lines);
            $order->statusLogs()->create(['to_status' => OrderStatus::Baru->value]);

            return $order->load('items');
        });
    }

    /**
     * Pindahkan status sesuai alur yang diizinkan dan catat di log audit.
     *
     * @throws InvalidOrderTransition jika perpindahan tidak diizinkan (mis. status sudah berubah oleh kasir lain)
     */
    public function transition(Order $order, OrderStatus $to, ?User $actor = null): Order
    {
        return DB::transaction(function () use ($order, $to, $actor) {
            // Kunci baris: dua kasir yang menekan tombol bersamaan tidak bisa melompati tahap.
            $locked = Order::whereKey($order->getKey())->lockForUpdate()->firstOrFail();
            $from = OrderStatus::from($locked->status);

            if (! $from->canMoveTo($to)) {
                throw new InvalidOrderTransition(
                    'Status pesanan sudah berubah atau tidak bisa dilanjutkan dari tahap ini. Muat ulang antrean.'
                );
            }

            $locked->update(['status' => $to->value]);
            $locked->statusLogs()->create([
                'user_id' => $actor?->getKey(),
                'from_status' => $from->value,
                'to_status' => $to->value,
            ]);

            return $locked;
        });
    }

    /** Format RK-yymmdd-XXXX tanpa karakter yang mudah tertukar (O/0, I/1). */
    public function generateCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $suffix = '';
            for ($i = 0; $i < 4; $i++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $code = 'RK-' . now()->format('ymd') . '-' . $suffix;
        } while (Order::where('code', $code)->exists());

        return $code;
    }
}
