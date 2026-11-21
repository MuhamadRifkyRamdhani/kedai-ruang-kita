<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'code', 'platform_order_id', 'source', 'table_number',
        'customer_name', 'notes', 'total', 'status',
    ];

    protected static function booted(): void
    {
        // Setiap pesanan otomatis punya token acak untuk URL publik.
        static::creating(function (Order $order) {
            if (empty($order->public_token)) {
                $order->public_token = Str::random(40);
            }
        });
    }

    /**
     * URL /pesanan/{order} dan /admin/pesanan/{order} memakai token acak,
     * bukan ID urut, sehingga nomor pesanan orang lain tidak bisa ditebak.
     * View tidak perlu diubah karena route('orders.show', $order) otomatis memakai ini.
     */
    public function getRouteKeyName(): string
    {
        return 'public_token';
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', OrderStatus::activeValues());
    }

    public function scopeFinished(Builder $query): Builder
    {
        return $query->whereIn('status', OrderStatus::finishedValues());
    }

    /** Pesanan yang dihitung sebagai pendapatan (siap + selesai). */
    public function scopeCountedAsRevenue(Builder $query): Builder
    {
        return $query->whereIn('status', OrderStatus::revenueValues());
    }
}
