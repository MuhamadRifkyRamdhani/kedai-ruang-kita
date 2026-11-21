<?php

return [
    // Akun awal yang dibuat oleh UserSeeder. Ganti lewat .env sebelum deployment.
    'admin_email' => env('ADMIN_EMAIL', 'admin@ruangkita.id'),
    'admin_password' => env('ADMIN_PASSWORD', 'ruangkita123'),
    'kasir_email' => env('KASIR_EMAIL', 'kasir@ruangkita.id'),
    'kasir_password' => env('KASIR_PASSWORD', 'kasirkita123'),

    // Webhook secret tiap platform. Kosong = webhook platform itu menolak request (503).
    'channels' => [
        'gofood' => ['webhook_secret' => env('GOFOOD_WEBHOOK_SECRET')],
        'grabfood' => ['webhook_secret' => env('GRABFOOD_WEBHOOK_SECRET')],
        'shopeefood' => ['webhook_secret' => env('SHOPEEFOOD_WEBHOOK_SECRET')],
    ],
];
