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
