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
        Schema::create('ticket_replies', function (Blueprint $table) {
            // id preserves WHMCS's original tblticketposts.id during migration.
            $table->id();
            $table->foreignId('ticket_id')->references('id')->on('support_tickets')->cascadeOnDelete();
            // Null when staff-authored (see admin_id below).
            $table->unsignedInteger('client_id')->nullable();
            $table->foreignId('admin_id')->nullable()->references('id')->on('admins')->nullOnDelete();
            $table->text('message');
            $table->timestamps();

            $table->index('ticket_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_replies');
    }
};
