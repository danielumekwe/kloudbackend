<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('client_id');
            $table->unsignedInteger('whmcs_invoice_id');
            $table->string('gateway'); // paystack, flutterwave, nowpayments
            $table->string('gateway_reference')->unique(); // idempotency key
            $table->decimal('amount', 12, 2);
            $table->string('currency');
            $table->string('status')->default('pending'); // pending, completed, failed
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index('client_id');
            $table->index('whmcs_invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
