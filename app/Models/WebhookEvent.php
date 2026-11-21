<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Catatan setiap webhook marketplace yang masuk, untuk audit dan diputar ulang. */
class WebhookEvent extends Model
{
    protected $fillable = [
        'platform', 'external_id', 'signature_valid', 'payload', 'status', 'error', 'order_id',
    ];

    protected function casts(): array
    {
        return [
            'signature_valid' => 'boolean',
            'payload' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
