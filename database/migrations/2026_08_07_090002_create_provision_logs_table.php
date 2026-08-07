<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every provisioning attempt (queued job try + cron sweep) for a VPS/Dedicated
     * order, so a failure can be diagnosed from the recorded API request/response
     * instead of only the final failure_reason on the order itself.
     */
    public function up(): void
    {
        Schema::create('provision_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('orderable'); // VpsOrder | DedicatedServerOrder
            $table->unsignedTinyInteger('attempt');
            $table->string('status'); // success, failed
            $table->json('request_payload')->nullable(); // redacted of rootpass
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['orderable_type', 'orderable_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provision_logs');
    }
};
