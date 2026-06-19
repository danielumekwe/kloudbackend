<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\SupportDepartment;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrateWhmcsTicketsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.whmcs' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('whmcs');

        Schema::connection('whmcs')->create('tbldepartments', function ($table) {
            $table->unsignedBigInteger('id');
            $table->string('name');
        });

        Schema::connection('whmcs')->create('tbltickets', function ($table) {
            $table->unsignedBigInteger('id');
            $table->unsignedInteger('userid');
            $table->unsignedBigInteger('did');
            $table->string('subject');
            $table->text('message');
            $table->string('status');
            $table->string('priority');
            $table->dateTime('date');
            $table->dateTime('lastreply')->nullable();
        });

        Schema::connection('whmcs')->create('tblticketposts', function ($table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('tid');
            $table->text('message');
            $table->dateTime('date');
            $table->string('admin')->nullable();
        });

        DB::connection('whmcs')->table('tbldepartments')->insert(['id' => 5, 'name' => 'Billing']);
        DB::connection('whmcs')->table('tbltickets')->insert([
            'id' => 101, 'userid' => 7, 'did' => 5, 'subject' => 'Old ticket', 'message' => 'Help please',
            'status' => 'Closed', 'priority' => 'High', 'date' => '2025-01-01 10:00:00', 'lastreply' => '2025-01-02 10:00:00',
        ]);
        DB::connection('whmcs')->table('tblticketposts')->insert([
            ['id' => 201, 'tid' => 101, 'message' => 'Original message', 'date' => '2025-01-01 10:00:00', 'admin' => null],
            ['id' => 202, 'tid' => 101, 'message' => 'Staff reply', 'date' => '2025-01-01 12:00:00', 'admin' => 'staff@admin.com'],
        ]);
    }

    public function test_migrates_departments_tickets_and_replies_preserving_ids(): void
    {
        Admin::create(['email' => 'staff@admin.com', 'password' => 'x', 'role' => 'support_agent']);

        $this->artisan('whmcs:migrate-tickets')->assertExitCode(0);

        $this->assertDatabaseHas('support_departments', ['id' => 5, 'name' => 'Billing']);
        $this->assertDatabaseHas('support_tickets', [
            'id' => 101, 'client_id' => 7, 'department_id' => 5, 'subject' => 'Old ticket', 'status' => 'Closed',
        ]);
        $this->assertDatabaseHas('ticket_replies', ['id' => 201, 'ticket_id' => 101, 'client_id' => 7, 'admin_id' => null]);

        $staffAdmin = Admin::where('email', 'staff@admin.com')->first();
        $this->assertDatabaseHas('ticket_replies', ['id' => 202, 'ticket_id' => 101, 'admin_id' => $staffAdmin->id]);
    }

    public function test_unmatched_staff_reply_imports_with_null_admin_id(): void
    {
        // No admins row for staff@admin.com exists this time.
        $this->artisan('whmcs:migrate-tickets')->assertExitCode(0);

        $this->assertDatabaseHas('ticket_replies', ['id' => 202, 'admin_id' => null]);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->artisan('whmcs:migrate-tickets', ['--dry-run' => true])->assertExitCode(0);

        $this->assertDatabaseCount('support_departments', 0);
        $this->assertDatabaseCount('support_tickets', 0);
        $this->assertDatabaseCount('ticket_replies', 0);
    }

    public function test_rerunning_is_idempotent(): void
    {
        $this->artisan('whmcs:migrate-tickets')->assertExitCode(0);
        $this->artisan('whmcs:migrate-tickets')->assertExitCode(0);

        $this->assertSame(1, SupportDepartment::count());
        $this->assertSame(1, SupportTicket::count());
        $this->assertSame(2, TicketReply::count());
    }
}
