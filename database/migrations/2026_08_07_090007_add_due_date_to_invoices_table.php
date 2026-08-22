<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Needed for the new payment-reminder/overdue email sweep (invoices:send-reminders) —
     * defaults new/existing rows to created_at + 7 days so nothing is retroactively overdue.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('due_date')->nullable()->after('total');
            $table->timestamp('reminder_sent_at')->nullable()->after('due_date');
            $table->timestamp('overdue_notified_at')->nullable()->after('reminder_sent_at');
        });

        $expr = DB::getDriverName() === 'sqlite'
            ? "datetime(created_at, '+7 days')"
            : 'DATE_ADD(created_at, INTERVAL 7 DAY)';

        DB::table('invoices')->whereNull('due_date')->update([
            'due_date' => DB::raw($expr),
        ]);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['due_date', 'reminder_sent_at', 'overdue_notified_at']);
        });
    }
};
