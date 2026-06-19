<?php

namespace Tests\Feature\Console;

use App\Models\DomainOrder;
use App\Models\QsOrder;
use App\Models\VpsOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CheckStuckProvisioningTest extends TestCase
{
    use RefreshDatabase;

    private function makeDomainOrder(string $status, ?\DateTimeInterface $updatedAt = null): DomainOrder
    {
        $order = DomainOrder::create([
            'client_id' => 7,
            'whmcs_invoice_id' => 42,
            'domain_name' => 'example',
            'tld' => 'com',
            'order_type' => 'register',
            'registration_years' => 1,
            'status' => $status,
            'price' => 15.00,
            'whois_privacy' => false,
            'registrant_contact' => ['first_name' => 'Jane'],
        ]);

        if ($updatedAt) {
            DomainOrder::whereKey($order->id)->update(['updated_at' => $updatedAt]);
            $order->refresh();
        }

        return $order;
    }

    public function test_reports_no_stuck_records_when_none_exist(): void
    {
        Log::spy();
        $this->makeDomainOrder('provisioned');

        $this->artisan('provisioning:check-stuck')->assertExitCode(0);

        Log::shouldNotHaveReceived('critical');
    }

    public function test_ignores_records_that_have_only_just_started_provisioning(): void
    {
        Log::spy();
        $this->makeDomainOrder('provisioning', now()->subMinutes(2));

        $this->artisan('provisioning:check-stuck')->assertExitCode(0);

        Log::shouldNotHaveReceived('critical');
    }

    public function test_flags_a_domain_order_stuck_provisioning_past_the_threshold(): void
    {
        Log::spy();
        $order = $this->makeDomainOrder('provisioning', now()->subMinutes(30));

        $this->artisan('provisioning:check-stuck')->assertExitCode(1);

        Log::shouldHaveReceived('critical')->once()->withArgs(
            fn (string $message, array $context) => $context['id'] === $order->id
                && $context['model'] === DomainOrder::class
        );
    }

    public function test_respects_the_minutes_option(): void
    {
        Log::spy();
        $this->makeDomainOrder('provisioning', now()->subMinutes(10));

        $this->artisan('provisioning:check-stuck', ['--minutes' => 30])->assertExitCode(0);

        Log::shouldNotHaveReceived('critical');
    }

    public function test_flags_stuck_records_across_other_order_types(): void
    {
        Log::spy();

        $vps = VpsOrder::create([
            'client_id' => 7,
            'category' => 'vps',
            'whmcs_invoice_id' => 1,
            'status' => 'provisioning',
            'price' => 20.00,
            'billing_cycle' => 'monthly',
            'config' => ['hostname' => 'vps1.example.com'],
        ]);
        VpsOrder::whereKey($vps->id)->update(['updated_at' => now()->subMinutes(30)]);

        $qs = QsOrder::create([
            'client_id' => 7,
            'whmcs_invoice_id' => 2,
            'status' => 'provisioning',
            'price' => 10.00,
            'billing_cycle' => 1,
            'config' => ['server' => 'qs-plan-1'],
        ]);
        QsOrder::whereKey($qs->id)->update(['updated_at' => now()->subMinutes(30)]);

        $this->artisan('provisioning:check-stuck')->assertExitCode(1);

        Log::shouldHaveReceived('critical')->twice();
    }
}
