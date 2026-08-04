<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_settings', function (Blueprint $table) {
            $table->id();
            $table->string('gateway');  // paystack, flutterwave, nowpayments
            $table->string('key_type'); // public_key, secret_key, webhook_hash, api_key, ipn_secret
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'key_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_settings');
    }
};
