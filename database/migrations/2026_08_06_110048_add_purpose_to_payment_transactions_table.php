<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Disambiguates invoice payments from wallet top-ups now that invoice_id can be
 * legitimately null for the latter (see Fund Wallet feature). Existing rows are
 * all invoice payments, so the default backfills them for free.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->string('purpose')->default('invoice')->after('currency'); // invoice, wallet_topup
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropColumn('purpose');
        });
    }
};
