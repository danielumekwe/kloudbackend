<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 of the WHMCS exit: invoicing moves fully local (see Invoice model).
 * whmcs_invoice_id -> invoice_id everywhere it appeared, now pointing at the
 * local invoices table. Kept as a bare nullable integer with no DB-level FK,
 * matching this codebase's existing convention for client_id (denormalized
 * reference, no FK, see clients table migration notes) — avoids a migration
 * failure on any stray pre-cutover test rows, which are not being preserved.
 */
return new class extends Migration
{
    // Table => column to place the new invoice_id after (each table's old
    // whmcs_invoice_id sat right after a different anchor column).
    private const TABLES = [
        'vps_orders'  => 'client_id',
        'qs_orders'   => 'client_id',
        'ssl_orders'  => 'client_id',
        'domain_orders' => 'client_id',
        'domain_renewals' => 'domain_order_id',
        'payment_transactions' => 'client_id',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::TABLES as $tableName => $afterColumn) {
            Schema::table($tableName, function (Blueprint $table) use ($afterColumn) {
                $table->unsignedBigInteger('invoice_id')->nullable()->after($afterColumn);
            });

            // payment_transactions is the only one of these six tables that had an
            // explicit index on whmcs_invoice_id — SQLite refuses to drop a column
            // a named index still references, so that index has to go first.
            if ($tableName === 'payment_transactions') {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropIndex(['whmcs_invoice_id']);
                });
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('whmcs_invoice_id');
            });

            if ($tableName === 'payment_transactions') {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->index('invoice_id');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::TABLES as $tableName => $afterColumn) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedInteger('whmcs_invoice_id')->nullable();
            });

            if ($tableName === 'payment_transactions') {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropIndex(['invoice_id']);
                });
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('invoice_id');
            });

            if ($tableName === 'payment_transactions') {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->index('whmcs_invoice_id');
                });
            }
        }
    }
};
