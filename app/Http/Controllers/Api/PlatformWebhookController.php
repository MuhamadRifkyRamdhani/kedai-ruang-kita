<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderSource;
use App\Http\Controllers\Controller;
use App\Models\WebhookEvent;
use App\Services\MarketplaceOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * POST /api/webhooks/{gofood|grabfood|shopeefood}
 *
 * Header X-Signature = HMAC-SHA256(body mentah, webhook secret platform dari .env).
 * Setiap request dicatat di webhook_events sebelum diproses.
 *
 * PENTING: format payload di bawah adalah format NORMALISASI buatan tim, bukan format resmi platform.
 * Setelah kredensial merchant dan dokumentasi API tiap platform tersedia, buat mapper per platform
 * yang mengubah payload resmi menjadi format ini sebelum divalidasi.
 */
class PlatformWebhookController extends Controller
{
    public function __construct(private MarketplaceOrderService $marketplace)
    {
    }

    public function handle(Request $request, string $platform): JsonResponse
    {
        $source = OrderSource::fromSlug($platform);
        abort_if($source === null, 404);

        $secret = config('ruangkita.channels.' . $source->slug() . '.webhook_secret');
        if (blank($secret)) {
            return response()->json(['message' => 'Webhook ' . $source->value . ' belum dikonfigurasi.'], 503);
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        if (! hash_equals($expected, (string) $request->header('X-Signature'))) {
            // Payload request tak terpercaya sengaja tidak disimpan.
            WebhookEvent::create([
                'platform' => $source->slug(),
                'signature_valid' => false,
                'status' => 'failed',
                'error' => 'Signature tidak valid.',
            ]);

            return response()->json(['message' => 'Signature tidak valid.'], 401);
        }

        $payload = $request->json()->all();
        $event = WebhookEvent::create([
            'platform' => $source->slug(),
            'external_id' => isset($payload['order_id']) ? (string) $payload['order_id'] : null,
            'signature_valid' => true,
            'payload' => $payload,
            'status' => 'received',
        ]);

        $validator = Validator::make($payload, [
            'order_id' => ['required', 'string', 'max:100'],
            'total' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:300'],
            'items' => ['sometimes', 'array', 'max:100'],
            'items.*.name' => ['required_with:items', 'string', 'max:120'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.price' => ['required_with:items', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            $event->update(['status' => 'failed', 'error' => $validator->errors()->toJson()]);

            return response()->json(['message' => 'Payload tidak valid.', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        try {
            $order = $this->marketplace->record(
                source: $source,
                total: (int) $data['total'],
                platformOrderId: $data['order_id'],
                notes: $data['notes'] ?? null,
                items: $data['items'] ?? [],
            );
        } catch (Throwable $e) {
            report($e);
            $event->update(['status' => 'failed', 'error' => $e->getMessage()]);

            // 500 membuat platform mencoba kirim ulang (retry); aman karena pemrosesan idempoten.
            return response()->json(['message' => 'Gagal memproses pesanan.'], 500);
        }

        $event->update([
            'status' => $order->wasRecentlyCreated ? 'processed' : 'duplicate',
            'order_id' => $order->id,
        ]);

        return response()->json([
            'order_code' => $order->code,
            'status' => $order->status,
        ], $order->wasRecentlyCreated ? 201 : 200);
    }
}
