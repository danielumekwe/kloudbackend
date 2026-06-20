<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Null = active. Set by an admin via the "Find Client" panel; checked at
            // login (LoginController) and on every request (ClientAuth middleware, so
            // an already-logged-in session is cut off immediately, not just blocked
            // from future logins).
            $table->timestamp('suspended_at')->nullable()->after('whmcs_client_id');
            // Null = unverified. Set when the client clicks the signed link from
            // VerifyEmailMail (see EmailVerificationController). Informational only —
            // does not gate dashboard/ordering access, by product decision.
            $table->timestamp('email_verified_at')->nullable()->after('suspended_at');
        });

        // Every client that existed before this column did already had a working,
        // logged-in account — treat them as verified rather than retroactively
        // flagging real customers as "unverified".
        DB::table('clients')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['suspended_at', 'email_verified_at']);
        });
    }
};
