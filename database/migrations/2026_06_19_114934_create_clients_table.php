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
        Schema::create('clients', function (Blueprint $table) {
            // id is NOT auto-increment-from-1 in production: the WHMCS migration
            // command preserves original tblclients.id values so every existing
            // order table's bare client_id integer resolves here without remapping.
            $table->id();
            $table->string('email')->unique();
            // Bcrypt going forward, or a legacy 32-char MD5 hash imported as-is from
            // WHMCS — see Client::checkPassword().
            $table->string('password');
            $table->string('firstname');
            $table->string('lastname');
            $table->string('phonenumber')->nullable();
            $table->string('address1')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postcode')->nullable();
            $table->string('country', 2)->nullable();
            // New — WHMCS's credit was always read-only display in this app. No admin
            // UI to adjust this yet; that's its own future feature.
            $table->decimal('credit_balance', 10, 2)->default(0);
            // For migrated clients this equals `id` (same WHMCS record). For clients
            // registered after this phase ships, WHMCS's AddClient assigns its own
            // fresh id here, unrelated to `id` — null if that shadow write failed.
            // Every remaining WHMCS call that needs a client id must resolve through
            // this column, never through `id` directly, or invoices/services can
            // silently resolve against an unrelated WHMCS client. See
            // App\Models\Client and the WHMCS-exit plan for the full rationale.
            $table->unsignedInteger('whmcs_client_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
