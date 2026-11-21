<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExternalOrderRequest;
use App\Services\MarketplaceOrderService;
use Illuminate\Http\RedirectResponse;

/**
 * Form "Catat pesanan marketplace" (input semi-manual).
 * Tetap dipertahankan sebagai fallback sampai integrasi API resmi aktif.
 */
class ExternalOrderController extends Controller
{
    public function __construct(private MarketplaceOrderService $marketplace)
    {
    }

    public function store(StoreExternalOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->marketplace->record(
            source: OrderSource::from($data['source']),
            total: (int) $data['total'],
            platformOrderId: $data['code'] ?? null,
            notes: $data['notes'] ?? null,
        );

        return redirect()->route('admin.orders')->with('success', 'Pesanan marketplace masuk ke daftar pesanan pusat.');
    }
}
