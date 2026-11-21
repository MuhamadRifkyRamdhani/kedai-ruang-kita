<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\StoreOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Pelanggan mengirim pesanan dan melihat konfirmasinya. */
class OrderController extends Controller
{
    public function __construct(private OrderService $orders)
    {
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $order = $this->orders->createFromCustomer($request->validated());

        return redirect()->route('orders.show', $order)->with('success', 'Pesanan berhasil dikirim ke kasir.');
    }

    /** {order} berisi token acak (lihat Order::getRouteKeyName), bukan ID urut. */
    public function show(Order $order): View
    {
        $order->load('items');

        return view('order-confirmation', compact('order'));
    }
}
