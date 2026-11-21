<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // ID pesanan di platform (GoFood dst.), terpisah dari kode internal RK-...
            $table->string('platform_order_id', 100)->nullable()->after('code');
            // Token acak untuk URL publik /pesanan/{token}, agar ID urut tidak bisa ditebak.
            $table->string('public_token', 64)->nullable()->after('platform_order_id');
        });

        // Isi token untuk pesanan lama.
        DB::table('orders')->whereNull('public_token')->orderBy('id')->each(function ($order) {
            DB::table('orders')->where('id', $order->id)->update(['public_token' => Str::random(40)]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unique('public_token');
            // Satu ID platform hanya boleh tercatat sekali per platform (anti pesanan ganda).
            $table->unique(['source', 'platform_order_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['source', 'platform_order_id']);
            $table->dropUnique(['public_token']);
            $table->dropIndex(['created_at']);
            $table->dropColumn(['platform_order_id', 'public_token']);
        });
    }
};
