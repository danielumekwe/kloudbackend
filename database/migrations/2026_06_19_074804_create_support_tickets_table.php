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
        Schema::create('support_tickets', function (Blueprint $table) {
            // id preserves WHMCS's original tbltickets.id during migration — see
            // App\Console\Commands\MigrateWhmcsTickets.
            $table->id();
            // Bare WHMCS client id, same convention as vps_orders.client_id — not yet
            // a real FK since no local clients table exists (that's a later phase).
            $table->unsignedInteger('client_id');
            $table->foreignId('department_id')->references('id')->on('support_departments');
            $table->string('subject');
            $table->text('message');
            $table->string('priority')->default('Medium'); // Low, Medium, High
            $table->string('status')->default('Open'); // Open, Answered, Customer-Reply, Closed
            $table->timestamp('last_reply_at')->nullable();
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
        Schema::dropIfExists('support_tickets');
    }
};
