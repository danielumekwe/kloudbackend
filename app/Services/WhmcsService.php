<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhmcsService
{
    private string $apiUrl;
    private string $identifier;
    private string $secret;

    public function __construct()
    {
        $this->apiUrl   = rtrim(config('services.whmcs.url'), '/') . '/includes/api.php';
        $this->identifier = config('services.whmcs.identifier');
        $this->secret   = config('services.whmcs.secret');
    }

    private function call(string $action, array $params = []): array
    {
        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post($this->apiUrl, array_merge([
                    'identifier'   => $this->identifier,
                    'secret'       => $this->secret,
                    'action'       => $action,
                    'responsetype' => 'json',
                ], $params));

            if (! $response->successful()) {
                Log::error("WHMCS API HTTP error [{$action}]", [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return ['result' => 'error', 'message' => 'API request failed (HTTP ' . $response->status() . ')'];
            }

            $data = $response->json();

            if (! is_array($data)) {
                Log::error("WHMCS API non-JSON response [{$action}]", ['body' => $response->body()]);
                return ['result' => 'error', 'message' => 'Invalid API response'];
            }

            return $data;
        } catch (\Exception $e) {
            Log::error("WHMCS API exception [{$action}]", ['error' => $e->getMessage()]);
            return ['result' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Ensure a potentially single-item associative array is wrapped in a list.
     * WHMCS returns a bare object when there is only one result instead of an array.
     */
    private function normalizeList(mixed $data): array
    {
        if (empty($data)) {
            return [];
        }
        if (array_is_list($data)) {
            return $data;
        }
        return [$data];
    }

    // -------------------------------------------------------------------------
    // Authentication
    // -------------------------------------------------------------------------

    public function validateLogin(string $email, string $password): array
    {
        return $this->call('ValidateLogin', [
            'email'     => $email,
            'password2' => $password,
        ]);
    }

    // -------------------------------------------------------------------------
    // Client management
    // -------------------------------------------------------------------------

    public function addClient(array $data): array
    {
        return $this->call('AddClient', $data);
    }

    public function getClientDetails(int $clientId): array
    {
        return $this->call('GetClientsDetails', [
            'clientid' => $clientId,
            'stats'    => true,
        ]);
    }

    public function getClientByEmail(string $email): array
    {
        return $this->call('GetClientsDetails', [
            'email' => $email,
            'stats' => false,
        ]);
    }

    public function updateClient(int $clientId, array $data): array
    {
        return $this->call('UpdateClient', array_merge(['clientid' => $clientId], $data));
    }

    /**
     * Switch a client's WHMCS account currency. Must succeed before any invoice is
     * created in that currency — WHMCS invoices always use the client's *current*
     * account currency, with no per-invoice currency override available.
     */
    public function switchClientCurrency(int $clientId, int $currencyId): array
    {
        return $this->updateClient($clientId, ['currency' => $currencyId]);
    }

    // -------------------------------------------------------------------------
    // Currencies
    // -------------------------------------------------------------------------

    public function getCurrencies(): array
    {
        $response = $this->call('GetCurrencies');
        if ($response['result'] !== 'success') {
            return [];
        }
        return $this->normalizeList($response['currencies']['currency'] ?? []);
    }

    // -------------------------------------------------------------------------
    // Products / Services
    // -------------------------------------------------------------------------

    public function getProducts(): array
    {
        $response = $this->call('GetProducts');
        if ($response['result'] !== 'success') {
            return [];
        }
        return $this->normalizeList($response['products']['product'] ?? []);
    }

    public function addOrder(array $data): array
    {
        return $this->call('AddOrder', $data);
    }

    public function getPaymentMethods(): array
    {
        $response = $this->call('GetPaymentMethods');
        if ($response['result'] !== 'success') {
            return [];
        }
        return $this->normalizeList($response['paymentmethods']['paymentmethod'] ?? []);
    }

    public function getOrders(int $clientId): array
    {
        $response = $this->call('GetOrders', ['clientid' => $clientId]);
        if ($response['result'] !== 'success') {
            return [];
        }
        return $this->normalizeList($response['orders']['order'] ?? []);
    }

    public function getClientServices(int $clientId): array
    {
        $response = $this->call('GetClientsProducts', ['clientid' => $clientId]);
        if ($response['result'] !== 'success') {
            return [];
        }
        return $this->normalizeList($response['products']['product'] ?? []);
    }

    public function getServiceDetails(int $serviceId): array
    {
        $response = $this->call('GetClientsProducts', ['serviceid' => $serviceId]);
        if ($response['result'] !== 'success') {
            return [];
        }
        $products = $this->normalizeList($response['products']['product'] ?? []);
        return $products[0] ?? [];
    }

    public function moduleCommand(int $serviceId, string $command, array $params = []): array
    {
        return $this->call('ModuleCommand', array_merge([
            'serviceid' => $serviceId,
            'command'   => $command,
        ], $params));
    }

    // -------------------------------------------------------------------------
    // Billing / Invoices
    // -------------------------------------------------------------------------

    public function getInvoices(int $clientId): array
    {
        $response = $this->call('GetInvoices', ['userid' => $clientId]);
        if ($response['result'] !== 'success') {
            return [];
        }
        return $this->normalizeList($response['invoices']['invoice'] ?? []);
    }

    public function getInvoice(int $invoiceId): array
    {
        return $this->call('GetInvoice', ['invoiceid' => $invoiceId]);
    }

    /**
     * Admin-wide invoice count for a given status, e.g. for dashboard stat cards.
     * limitnum=1 keeps this cheap — we only read the `totalresults` count, not the
     * actual invoice rows.
     */
    public function getInvoiceCountByStatus(string $status): int
    {
        $response = $this->call('GetInvoices', ['status' => $status, 'limitnum' => 1]);

        if (($response['result'] ?? '') !== 'success') {
            return 0;
        }

        return (int) ($response['totalresults'] ?? count($this->normalizeList($response['invoices']['invoice'] ?? [])));
    }

    /**
     * Raw unpaid + overdue invoices, capped at 200 of each (a single page) to keep
     * this cheap on every dashboard load. Returns the raw rows (with `currencycode`)
     * rather than a sum, since invoices can be in different currencies and only the
     * caller knows how to convert those to a common base — see
     * CurrencyConverter::convertToUsd(). On accounts with more outstanding invoices
     * than 200+200, this undercounts — it's a dashboard estimate, not a ledger total.
     */
    public function getOutstandingInvoices(): array
    {
        $unpaid = $this->call('GetInvoices', ['status' => 'Unpaid', 'limitnum' => 200]);
        $overdue = $this->call('GetInvoices', ['status' => 'Overdue', 'limitnum' => 200]);

        return array_merge(
            $this->normalizeList($unpaid['invoices']['invoice'] ?? []),
            $this->normalizeList($overdue['invoices']['invoice'] ?? [])
        );
    }

    public function createInvoice(int $clientId, string $description, float $amount): array
    {
        return $this->call('CreateInvoice', [
            'userid'           => $clientId,
            'itemdescription1' => $description,
            'itemamount1'      => number_format($amount, 2, '.', ''),
            // No paymentmethod: forcing one (e.g. "mailin") breaks the invoice if that
            // gateway module isn't active in WHMCS. Leaving it unset lets the client
            // pick from whatever gateways actually are active.
        ]);
    }

    /**
     * Records an out-of-band payment (collected by us via Paystack/Flutterwave/
     * NOWPayments, never through a WHMCS gateway module) against an invoice. This is
     * what actually marks the invoice Paid in WHMCS — "gateway" here is just a label
     * for reporting, not a reference to an active WHMCS gateway module.
     */
    public function addInvoicePayment(int $invoiceId, string $transactionId, float $amount, string $gateway): array
    {
        return $this->call('AddInvoicePayment', [
            'invoiceid' => $invoiceId,
            'transid'   => $transactionId,
            'amount'    => number_format($amount, 2, '.', ''),
            'gateway'   => $gateway,
        ]);
    }

    // -------------------------------------------------------------------------
    // Support Tickets
    // -------------------------------------------------------------------------

    /**
     * Admin-wide open ticket count (no clientid filter) for dashboard stat cards.
     * limitnum=1 keeps this cheap — we only read the `totalresults` count.
     */
    public function getOpenTicketCount(): int
    {
        $response = $this->call('GetTickets', ['status' => 'Open', 'limitnum' => 1]);

        if (($response['result'] ?? '') !== 'success') {
            return 0;
        }

        return (int) ($response['totalresults'] ?? count($this->normalizeList($response['tickets']['ticket'] ?? [])));
    }

    public function getSupportDepartments(): array
    {
        $response = $this->call('GetSupportDepartments');
        if ($response['result'] !== 'success') {
            return [];
        }
        return $this->normalizeList($response['departments']['department'] ?? []);
    }

    public function getTickets(int $clientId): array
    {
        $response = $this->call('GetTickets', ['clientid' => $clientId]);
        if ($response['result'] !== 'success') {
            return [];
        }
        return $this->normalizeList($response['tickets']['ticket'] ?? []);
    }

    public function getTicket(int $ticketId): array
    {
        return $this->call('GetTicket', ['ticketid' => $ticketId]);
    }

    public function openTicket(array $data): array
    {
        return $this->call('OpenTicket', $data);
    }

    public function replyTicket(int $ticketId, int $clientId, string $message): array
    {
        return $this->call('AddTicketReply', [
            'ticketid' => $ticketId,
            'clientid' => $clientId,
            'message'  => $message,
        ]);
    }

    public function closeTicket(int $ticketId): array
    {
        return $this->call('CloseTicket', ['ticketid' => $ticketId]);
    }
}
