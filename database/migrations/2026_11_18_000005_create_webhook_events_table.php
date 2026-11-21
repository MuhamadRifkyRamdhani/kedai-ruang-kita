<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 20)->index();
            $table->string('external_id', 100)->nullable()->index();
            $table->boolean('signature_valid')->default(false);
            $table->longText('payload')->nullable();
            $table->string('status', 20)->default('received'); // received | processed | duplicate | failed
            $table->text('error')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
