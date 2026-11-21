<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransition;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Antrean dapur: tiga kolom + tombol pindah status. */
class OrderBoardController extends Controller
{
    public function __construct(private OrderService $orders)
    {
    }

    public function index(): View
    {
        $queue = fn (OrderStatus $status) => Order::with('items')
            ->where('status', $status->value)
            ->oldest('created_at')
            ->oldest('id')
            ->get();

        $baru = $queue(OrderStatus::Baru);
        $diproses = $queue(OrderStatus::Diproses);
        $siap = $queue(OrderStatus::Siap);
        $activeCount = $baru->count() + $diproses->count() + $siap->count();

        return view('admin.orders-board', compact('baru', 'diproses', 'siap', 'activeCount'));
    }

    /** PATCH /admin/pesanan/{order}  (field: status) */
    public function update(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        try {
            $order = $this->orders->transition(
                $order,
                OrderStatus::from($request->validated('status')),
                $request->user(),
            );
        } catch (InvalidOrderTransition $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pesanan ' . $order->code . ' dipindahkan ke tahap ' . $order->status . '.');
    }
}
