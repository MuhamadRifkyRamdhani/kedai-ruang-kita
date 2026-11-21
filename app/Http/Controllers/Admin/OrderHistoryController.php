<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Riwayat pesanan selesai/dibatalkan. GET /admin/pesanan/riwayat?status=selesai|dibatalkan */
class OrderHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), OrderStatus::finishedValues(), true)
            ? $request->query('status')
            : null;

        $orders = Order::with('items')
            ->whereIn('status', $status ? [$status] : OrderStatus::finishedValues())
            ->latest()
            ->paginate(20);

        return view('admin.orders', compact('orders', 'status'));
    }
}
