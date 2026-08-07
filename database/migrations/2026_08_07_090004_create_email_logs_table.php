<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Automatically populated for every outgoing email app-wide via a listener
     * on Illuminate\Mail\Events\MessageSent (see AppServiceProvider) — no
     * changes needed to any existing Mailable or call site. body_html is
     * stored so an admin "Resend" can re-send the exact rendered email without
     * needing to reconstruct the original Mailable's constructor arguments.
     */
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('to_email');
            $table->string('subject');
            $table->string('mailable_class')->nullable();
            $table->nullableMorphs('related');
            $table->longText('body_html')->nullable();
            $table->string('status')->default('sent'); // sent, failed
            $table->text('error')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['to_email', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
