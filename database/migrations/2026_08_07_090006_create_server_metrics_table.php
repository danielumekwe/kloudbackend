<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hourly resource-usage snapshots, collected by the servers:collect-metrics
     * scheduled command from InterServer's traffic_usage endpoint. Powers the
     * usage chart on the customer/admin server detail pages.
     */
    public function up(): void
    {
        Schema::create('server_metrics', function (Blueprint $table) {
            $table->id();
            $table->morphs('orderable'); // VpsOrder | DedicatedServerOrder
            $table->decimal('bandwidth_usage_gb', 12, 2)->nullable();
            $table->decimal('disk_usage_gb', 12, 2)->nullable();
            $table->decimal('cpu_usage_percent', 5, 2)->nullable();
            $table->decimal('ram_usage_percent', 5, 2)->nullable();
            $table->timestamp('recorded_at')->useCurrent();

            $table->index(['orderable_type', 'orderable_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_metrics');
    }
};
