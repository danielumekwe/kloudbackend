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
        Schema::table('support_tickets', function (Blueprint $table) {
            // Which of the client's services this ticket is about (vps/ssl/domain/qs),
            // and the order id within that table — both nullable since a ticket isn't
            // required to be about a specific service. Looked up at display time via
            // TicketService::resolveRelatedServiceLabel() rather than denormalized,
            // so it always reflects the service's current state (e.g. hostname).
            $table->string('related_service_type', 20)->nullable()->after('priority');
            $table->unsignedBigInteger('related_service_id')->nullable()->after('related_service_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn(['related_service_type', 'related_service_id']);
        });
    }
};
