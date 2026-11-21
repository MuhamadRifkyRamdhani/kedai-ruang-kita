<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Jejak audit perubahan status: siapa, dari status apa, ke status apa, kapan. */
class OrderStatusLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['order_id', 'user_id', 'from_status', 'to_status'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
