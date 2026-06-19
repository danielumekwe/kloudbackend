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
        Schema::create('domain_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_order_id')->references('id')->on('domain_orders')->cascadeOnDelete();
            $table->unsignedInteger('whmcs_invoice_id')->nullable();
            $table->unsignedTinyInteger('years');
            $table->decimal('price', 10, 2);
            $table->string('status')->default('pending_payment'); // pending_payment, completed, failed
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_renewals');
    }
};
