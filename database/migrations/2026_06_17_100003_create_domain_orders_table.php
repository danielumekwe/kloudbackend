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
        Schema::create('domain_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('client_id');
            $table->unsignedInteger('whmcs_invoice_id')->nullable();
            $table->unsignedInteger('interserver_domain_id')->nullable();
            $table->string('domain_name');
            $table->string('tld');
            $table->string('order_type')->default('register'); // register, transfer
            $table->unsignedTinyInteger('registration_years')->default(1);
            $table->string('status')->default('pending_payment'); // pending_payment, paid, provisioned, failed, cancelled
            $table->decimal('price', 10, 2);
            $table->boolean('whois_privacy')->default(false);
            $table->json('registrant_contact');
            $table->json('config')->nullable(); // transfer auth code, extra per-TLD fields, etc.
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index('client_id');
            $table->index('status');
            $table->index(['domain_name', 'tld']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_orders');
    }
};
