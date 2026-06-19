<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrateWhmcsClientsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.whmcs' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('whmcs');

        Schema::connection('whmcs')->create('tblclients', function ($table) {
            $table->unsignedBigInteger('id');
            $table->string('email');
            $table->string('password');
            $table->string('firstname');
            $table->string('lastname');
            $table->string('phonenumber')->nullable();
            $table->string('address1')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postcode')->nullable();
            $table->string('country')->nullable();
            $table->decimal('credit', 10, 2)->default(0);
        });

        DB::connection('whmcs')->table('tblclients')->insert([
            'id' => 301, 'email' => 'jane@example.com', 'password' => Hash::make('password123'),
            'firstname' => 'Jane', 'lastname' => 'Doe', 'phonenumber' => '+2348000000000',
            'address1' => '1 Main St', 'city' => 'Lagos', 'state' => 'Lagos', 'postcode' => '100001',
            'country' => 'NG', 'credit' => 12.50,
        ]);
        DB::connection('whmcs')->table('tblclients')->insert([
            'id' => 302, 'email' => 'old@example.com', 'password' => md5('legacy-password'),
            'firstname' => 'Old', 'lastname' => 'Account', 'credit' => 0,
        ]);
        DB::connection('whmcs')->table('tblclients')->insert([
            'id' => 303, 'email' => 'mystery@example.com', 'password' => 'some-unrecognized-hash-format',
            'firstname' => 'Mystery', 'lastname' => 'Hash', 'credit' => 0,
        ]);
    }

    public function test_migrates_clients_preserving_id_as_local_id_and_whmcs_client_id(): void
    {
        $this->artisan('whmcs:migrate-clients')->assertExitCode(0);

        $this->assertDatabaseHas('clients', [
            'id' => 301, 'whmcs_client_id' => 301, 'email' => 'jane@example.com',
            'firstname' => 'Jane', 'credit_balance' => 12.50,
        ]);
    }

    public function test_imports_bcrypt_password_and_login_works(): void
    {
        $this->artisan('whmcs:migrate-clients')->assertExitCode(0);

        $client = Client::find(301);
        $this->assertTrue($client->checkPassword('password123'));
    }

    public function test_imports_legacy_md5_password_and_login_works(): void
    {
        $this->artisan('whmcs:migrate-clients')->assertExitCode(0);

        $client = Client::find(302);
        $this->assertTrue($client->checkPassword('legacy-password'));
    }

    public function test_unrecognized_hash_format_imports_without_erroring_but_cannot_log_in(): void
    {
        $this->artisan('whmcs:migrate-clients')->assertExitCode(0);

        $client = Client::find(303);
        $this->assertNotNull($client);
        $this->assertFalse($client->checkPassword('anything'));
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->artisan('whmcs:migrate-clients', ['--dry-run' => true])->assertExitCode(0);

        $this->assertDatabaseCount('clients', 0);
    }

    public function test_rerunning_is_idempotent(): void
    {
        $this->artisan('whmcs:migrate-clients')->assertExitCode(0);
        $this->artisan('whmcs:migrate-clients')->assertExitCode(0);

        $this->assertSame(3, Client::count());
    }
}
