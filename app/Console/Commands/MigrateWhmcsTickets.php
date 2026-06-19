<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\SupportDepartment;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time historical import from the real WHMCS database (Phase 1 of the
 * WHMCS exit — see /Users/Apple/.claude/plans/hidden-baking-gem.md). Reads
 * from the read-only `whmcs` connection (config/database.php), never writes
 * to it. Safe to re-run — every insert is keyed by the original WHMCS id via
 * updateOrCreate(), so this can be run as a dry-run-then-final-delta pass
 * right before cutover.
 *
 * Column names below assume a standard WHMCS schema (tbldepartments,
 * tbltickets, tblticketposts) and have NOT been verified against this
 * specific WHMCS install — run `DESCRIBE tbltickets;` etc. against the real
 * DB first and adjust if anything differs before the real cutover run.
 */
#[Signature('whmcs:migrate-tickets {--dry-run : Report counts without writing anything}')]
#[Description('One-time import of departments/tickets/replies from the real WHMCS database')]
class MigrateWhmcsTickets extends Command
{
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->importDepartments($dryRun);
        $this->importTickets($dryRun);
        $this->importReplies($dryRun);

        return self::SUCCESS;
    }

    private function importDepartments(bool $dryRun): void
    {
        $rows = DB::connection('whmcs')->table('tbldepartments')->get(['id', 'name']);
        $this->info("Departments found in WHMCS: {$rows->count()}");

        if ($dryRun) {
            return;
        }

        foreach ($rows as $row) {
            $this->upsertById(SupportDepartment::class, $row->id, ['name' => $row->name]);
        }
    }

    private function importTickets(bool $dryRun): void
    {
        $rows = DB::connection('whmcs')->table('tbltickets')
            ->get(['id', 'userid', 'did', 'subject', 'message', 'status', 'priority', 'date', 'lastreply']);
        $this->info("Tickets found in WHMCS: {$rows->count()}");

        if ($dryRun) {
            return;
        }

        foreach ($rows as $row) {
            $this->upsertById(SupportTicket::class, $row->id, [
                'client_id' => $row->userid,
                'department_id' => $row->did,
                'subject' => $row->subject,
                'message' => $row->message,
                'priority' => $row->priority,
                'status' => $row->status,
                'last_reply_at' => $row->lastreply,
                'created_at' => $row->date,
            ]);
        }
    }

    /**
     * tblticketposts.admin is a free-text name/username with no FK target in the
     * new `admins` table — resolved by matching against admins.email where
     * possible. Unmatched staff replies are imported with admin_id = null and the
     * original name logged, rather than blocking the migration on it.
     */
    private function importReplies(bool $dryRun): void
    {
        $rows = DB::connection('whmcs')->table('tblticketposts')
            ->get(['id', 'tid', 'message', 'date', 'admin']);
        $this->info("Ticket replies found in WHMCS: {$rows->count()}");

        if ($dryRun) {
            return;
        }

        $ticketOwners = SupportTicket::pluck('client_id', 'id');
        $adminsByEmail = Admin::pluck('id', 'email');

        foreach ($rows as $row) {
            $isStaffReply = ! empty($row->admin);
            $adminId = $isStaffReply ? ($adminsByEmail[$row->admin] ?? null) : null;

            if ($isStaffReply && $adminId === null) {
                $this->warn("Reply #{$row->id}: could not match WHMCS admin \"{$row->admin}\" to an admins row — imported with admin_id = null.");
            }

            $this->upsertById(TicketReply::class, $row->id, [
                'ticket_id' => $row->tid,
                'client_id' => $isStaffReply ? null : ($ticketOwners[$row->tid] ?? null),
                'admin_id' => $adminId,
                'message' => $row->message,
                'created_at' => $row->date,
            ]);
        }
    }

    /**
     * Model::updateOrCreate(['id' => ...], ...) silently drops the id on the create
     * path — Eloquent's newInstance() still goes through fill(), which respects
     * $fillable, and `id` is deliberately not fillable on any of these models (mass-
     * assigning a primary key from user input would be a real vulnerability). This
     * is trusted internal migration code, not user input, so forceFill() — which
     * bypasses that guard — is the correct tool here instead of changing the models.
     *
     * @param class-string<\Illuminate\Database\Eloquent\Model> $modelClass
     */
    private function upsertById(string $modelClass, int $id, array $attributes): void
    {
        $model = $modelClass::find($id) ?? new $modelClass();

        $model->forceFill(array_merge(['id' => $id], $attributes))->save();
    }
}
