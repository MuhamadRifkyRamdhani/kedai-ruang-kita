<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;

class DashboardService
{
    public const PERIODS = ['hari', 'minggu', 'bulan'];

    /**
     * Nama kunci sama dengan variabel yang dipakai resources/views/admin/dashboard.blade.php.
     *
     * @return array<string, mixed>
     */
    public function summary(string $period): array
    {
        $period = in_array($period, self::PERIODS, true) ? $period : 'hari';
        $from = $this->periodStart($period);

        $counted = Order::countedAsRevenue()->where('created_at', '>=', $from);

        return [
            'period' => $period,
            'revenue' => (int) (clone $counted)->sum('total'),
            'count' => (clone $counted)->count(),
            'bestSellers' => $this->bestSellers($from),
            'recentOrders' => Order::with('items')->latest()->take(6)->get(),
            'pending' => Order::whereIn('status', [OrderStatus::Baru->value, OrderStatus::Diproses->value, OrderStatus::Siap->value])->count(),
            'dailySales' => $this->dailySales(),
        ];
    }

    private function periodStart(string $period): Carbon
    {
        return match ($period) {
            'minggu' => now()->startOfWeek(),
            'bulan' => now()->startOfMonth(),
            default => now()->startOfDay(),
        };
    }

    /** Menu terlaris: kolom product_name dan units (jumlah porsi). */
    private function bestSellers(Carbon $from)
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', OrderStatus::revenueValues())
            ->where('orders.created_at', '>=', $from)
            ->selectRaw('order_items.product_name, SUM(order_items.quantity) as units')
            ->groupBy('order_items.product_name')
            ->orderByDesc('units')
            ->take(5)
            ->get();
    }

    /**
     * Pendapatan 7 hari terakhir, urut dari terlama ke hari ini.
     * Satu query, hari tanpa transaksi bernilai 0.
     *
     * @return \Illuminate\Support\Collection<int, array{label:string, value:int}>
     */
    private function dailySales()
    {
        $start = now()->subDays(6)->startOfDay();

        $byDay = Order::countedAsRevenue()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, SUM(total) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(0, 6))->map(function ($offset) use ($start, $byDay) {
            $date = $start->copy()->addDays($offset);

            return [
                'label' => $date->translatedFormat('D'),
                'value' => (int) ($byDay[$date->format('Y-m-d')] ?? 0),
            ];
        });
    }
}
