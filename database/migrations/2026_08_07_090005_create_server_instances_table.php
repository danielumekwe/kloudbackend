<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Normalized, fast local read model for a provisioned server — upserted by
     * ProvisionServerOrder on success. VpsOrder/DedicatedServerOrder remain the
     * source of truth for billing/order status; this table exists so admin and
     * customer pages don't need a live InterServer call on every page load.
     */
    public function up(): void
    {
        Schema::create('server_instances', function (Blueprint $table) {
            $table->id();
            $table->morphs('orderable'); // VpsOrder | DedicatedServerOrder
            $table->unsignedInteger('client_id');
            $table->unsignedInteger('interserver_id')->nullable(); // vps_id / server_id
            $table->string('hostname')->nullable();
            $table->string('ipv4')->nullable();
            $table->string('ipv6')->nullable();
            $table->unsignedInteger('ssh_port')->default(22);
            $table->string('root_username')->default('root');
            $table->text('root_password_encrypted')->nullable();
            $table->string('os')->nullable();
            $table->string('cpu')->nullable();
            $table->string('ram')->nullable();
            $table->string('disk')->nullable();
            $table->string('bandwidth')->nullable();
            $table->string('location')->nullable();
            $table->string('status')->default('active'); // active, suspended, terminated
            $table->timestamp('provisioned_at')->nullable();
            $table->timestamp('renewal_at')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_instances');
    }
};
