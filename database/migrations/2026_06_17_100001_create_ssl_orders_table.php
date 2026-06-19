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
        Schema::create('ssl_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('client_id');
            $table->unsignedInteger('whmcs_invoice_id')->nullable();
            $table->unsignedInteger('interserver_ssl_id')->nullable();
            $table->string('status')->default('pending_payment'); // pending_payment, paid, provisioned, failed, cancelled
            $table->decimal('price', 10, 2);
            $table->string('billing_cycle');
            $table->json('config'); // package_id, hostname, approver_email, csr_type, csr, contact overrides
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index('client_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ssl_orders');
    }
};
