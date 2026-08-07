<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Single table backing both the admin audit log and the customer-facing
     * activity timeline — same rows, filtered per audience at read time.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('subject'); // e.g. VpsOrder, DedicatedServerOrder, Invoice
            $table->string('causer_type')->nullable(); // 'admin' | 'client' | 'system'
            $table->unsignedInteger('causer_id')->nullable();
            $table->string('action'); // e.g. 'vps.started', 'invoice.paid', 'password.reset'
            $table->text('description');
            $table->json('properties')->nullable();
            $table->boolean('visible_to_client')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
