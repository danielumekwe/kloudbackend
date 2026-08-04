<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dedicated_server_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('client_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedInteger('interserver_server_id')->nullable();
            $table->string('status')->default('pending_payment'); // pending_payment, paid, provisioning, provisioned, failed, cancelled
            $table->decimal('price', 10, 2);
            $table->string('billing_cycle'); // monthly
            $table->json('config'); // asset_id, hardware snapshot, hostname, os/bandwidth/ips/cp/raid ids, rootpass (encrypted)
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index('client_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dedicated_server_orders');
    }
};
