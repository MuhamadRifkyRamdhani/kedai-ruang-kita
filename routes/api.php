<?php

use App\Http\Controllers\Api\PlatformWebhookController;
use Illuminate\Support\Facades\Route;

// Otomatis berprefix /api dan tanpa CSRF. Contoh: POST /api/webhooks/gofood
Route::post('/webhooks/{platform}', [PlatformWebhookController::class, 'handle'])
    ->middleware('throttle:60,1')
    ->name('webhooks.handle');
