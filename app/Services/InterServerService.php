<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InterServerService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.interserver.url'), '/');
        $this->apiKey  = config('services.interserver.api_key');
    }

    private function request(string $method, string $path, array $data = []): array
    {
        try {
            $response = Http::withHeaders(['X-API-KEY' => $this->apiKey])
                ->timeout(30)
                ->{$method}($this->baseUrl . $path, $data);

            if (! $response->successful()) {
                Log::error("InterServer API HTTP error [{$method} {$path}]", [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return ['error' => true, 'message' => 'InterServer API request failed (HTTP ' . $response->status() . ')', 'status' => $response->status()];
            }

            $data = $response->json();

            return is_array($data) ? $data : ['error' => true, 'message' => 'Invalid InterServer API response'];
        } catch (\Exception $e) {
            Log::error("InterServer API exception [{$method} {$path}]", ['error' => $e->getMessage()]);
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    // -------------------------------------------------------------------------
    // VPS listing / detail
    // -------------------------------------------------------------------------

    public function listVps(): array
    {
        $result = $this->request('get', '/vps');
        return is_array($result) && ! ($result['error'] ?? false) ? $result : [];
    }

    public function getVps(int $id): array
    {
        return $this->request('get', "/vps/{$id}");
    }

    // -------------------------------------------------------------------------
    // Order catalog
    // -------------------------------------------------------------------------

    public function getOrderCatalog(): array
    {
        return $this->request('get', '/vps/order');
    }

    public function quoteOrder(array $config): array
    {
        return $this->request('put', '/vps/order', $config);
    }

    public function placeOrder(array $config): array
    {
        return $this->request('post', '/vps/order', $config);
    }

    // -------------------------------------------------------------------------
    // Lifecycle
    // -------------------------------------------------------------------------

    public function startVps(int $id): array
    {
        return $this->request('get', "/vps/{$id}/start");
    }

    public function stopVps(int $id): array
    {
        return $this->request('get', "/vps/{$id}/stop");
    }

    public function restartVps(int $id): array
    {
        return $this->request('get', "/vps/{$id}/restart");
    }

    public function cancelVps(int $id): array
    {
        return $this->request('delete', "/vps/{$id}");
    }

    // -------------------------------------------------------------------------
    // Reinstall / passwords
    // -------------------------------------------------------------------------

    public function getReinstallTemplates(int $id): array
    {
        return $this->request('get', "/vps/{$id}/reinstall_os");
    }

    public function reinstallOs(int $id, string $template, string $localPassword, ?string $password = null): array
    {
        $payload = ['template' => $template, 'localPassword' => $localPassword];
        if ($password) {
            $payload['password'] = $password;
        }
        return $this->request('post', "/vps/{$id}/reinstall_os", $payload);
    }

    public function changeRootPassword(int $id, string $password): array
    {
        return $this->request('post', "/vps/{$id}/change_root_password", ['password' => $password]);
    }

    public function resetPassword(int $id): array
    {
        return $this->request('post', "/vps/{$id}/reset_password");
    }

    // -------------------------------------------------------------------------
    // Backups / restore
    // -------------------------------------------------------------------------

    public function getVpsBackup(int $id): array
    {
        return $this->request('get', "/vps/{$id}/backup");
    }

    public function getVpsBackups(int $id): array
    {
        return $this->request('get', "/vps/{$id}/backups");
    }

    public function deleteVpsBackup(int $id, string $file): array
    {
        return $this->request('delete', "/vps/{$id}/backups?" . http_build_query(['file' => $file]));
    }

    public function downloadVpsBackup(int $id, string $file): array
    {
        return $this->request('patch', "/vps/{$id}/backups", ['file' => $file]);
    }

    public function restoreVps(int $id, string $backup, string $password): array
    {
        return $this->request('post', "/vps/{$id}/restore", ['backup' => $backup, 'password' => $password]);
    }

    // -------------------------------------------------------------------------
    // Disk / IP add-ons
    // -------------------------------------------------------------------------

    public function getBuyHdSpace(int $id): array
    {
        return $this->request('get', "/vps/{$id}/buy_hd_space");
    }

    public function quoteBuyHdSpace(int $id, int $size): array
    {
        return $this->request('put', "/vps/{$id}/buy_hd_space", ['size' => $size]);
    }

    public function buyHdSpace(int $id, int $size): array
    {
        return $this->request('post', "/vps/{$id}/buy_hd_space", ['size' => $size]);
    }

    public function getBuyIp(int $id): array
    {
        return $this->request('get', "/vps/{$id}/buy_ip");
    }

    public function buyIp(int $id): array
    {
        return $this->request('post', "/vps/{$id}/buy_ip");
    }

    // -------------------------------------------------------------------------
    // Hostname / DNS
    // -------------------------------------------------------------------------

    public function getChangeHostname(int $id): array
    {
        return $this->request('get', "/vps/{$id}/change_hostname");
    }

    public function changeHostname(int $id, string $hostname): array
    {
        return $this->request('post', "/vps/{$id}/change_hostname", ['hostname' => $hostname]);
    }

    public function getReverseDns(int $id): array
    {
        return $this->request('get', "/vps/{$id}/reverse_dns");
    }

    public function setReverseDns(int $id, array $ips): array
    {
        return $this->request('post', "/vps/{$id}/reverse_dns", ['ips' => $ips]);
    }

    // -------------------------------------------------------------------------
    // Security / CD / quota
    // -------------------------------------------------------------------------

    public function blockSmtp(int $id): array
    {
        return $this->request('get', "/vps/{$id}/block_smtp");
    }

    public function disableCd(int $id): array
    {
        return $this->request('get', "/vps/{$id}/disable_cd");
    }

    public function ejectCd(int $id): array
    {
        return $this->request('get', "/vps/{$id}/eject_cd");
    }

    public function getInsertCd(int $id): array
    {
        return $this->request('get', "/vps/{$id}/insert_cd");
    }

    public function insertCd(int $id, string $url): array
    {
        return $this->request('post', "/vps/{$id}/insert_cd", ['url' => $url]);
    }

    public function disableQuota(int $id): array
    {
        return $this->request('get', "/vps/{$id}/disable_quota");
    }

    public function enableQuota(int $id): array
    {
        return $this->request('get', "/vps/{$id}/enable_quota");
    }

    // -------------------------------------------------------------------------
    // Password pre-flight checks
    // -------------------------------------------------------------------------

    public function getChangeRootPasswordInfo(int $id): array
    {
        return $this->request('get', "/vps/{$id}/change_root_password");
    }

    public function getResetPasswordInfo(int $id): array
    {
        return $this->request('get', "/vps/{$id}/reset_password");
    }

    // -------------------------------------------------------------------------
    // Timezone / slices / remote access
    // -------------------------------------------------------------------------

    public function getChangeTimezone(int $id): array
    {
        return $this->request('get', "/vps/{$id}/change_timezone");
    }

    public function changeTimezone(int $id, string $timezone): array
    {
        return $this->request('post', "/vps/{$id}/change_timezone", ['timezone' => $timezone]);
    }

    public function getSlices(int $id): array
    {
        return $this->request('get', "/vps/{$id}/slices");
    }

    public function resizeSlices(int $id, int $slices): array
    {
        return $this->request('post', "/vps/{$id}/slices", ['slices' => $slices]);
    }

    public function getSetupVnc(int $id): array
    {
        return $this->request('get', "/vps/{$id}/setup_vnc");
    }

    public function setupVnc(int $id, string $vnc): array
    {
        return $this->request('post', "/vps/{$id}/setup_vnc", ['vnc' => $vnc]);
    }

    public function getViewDesktop(int $id): array
    {
        return $this->request('get', "/vps/{$id}/view_desktop");
    }

    public function refreshViewDesktop(int $id): array
    {
        return $this->request('post', "/vps/{$id}/view_desktop");
    }

    // -------------------------------------------------------------------------
    // Misc
    // -------------------------------------------------------------------------

    public function updateVpsInfo(int $id, array $data): array
    {
        return $this->request('post', "/vps/{$id}", $data);
    }

    public function getVpsInvoices(int $id): array
    {
        return $this->request('get', "/vps/{$id}/invoices");
    }

    public function getTrafficUsage(int $id): array
    {
        return $this->request('get', "/vps/{$id}/traffic_usage");
    }

    public function getTrafficUsageFiltered(int $id, array $filters): array
    {
        return $this->request('post', "/vps/{$id}/traffic_usage", $filters);
    }

    public function getWelcomeEmail(int $id): array
    {
        return $this->request('get', "/vps/{$id}/welcome_email");
    }

    public function changeWebuzoPassword(int $id, string $password): array
    {
        return $this->request('post', "/vps/{$id}/change_webuzo_password", ['password' => $password]);
    }

    // ===========================================================================
    // SSL Certificates
    // ===========================================================================

    public function listSsl(): array
    {
        $result = $this->request('get', '/ssl');
        return is_array($result) && ! ($result['error'] ?? false) ? $result : [];
    }

    public function getSslOrderCatalog(): array
    {
        return $this->request('get', '/ssl/order');
    }

    public function quoteSslOrder(array $config): array
    {
        return $this->request('put', '/ssl/order', $config);
    }

    public function placeSslOrder(array $config): array
    {
        return $this->request('post', '/ssl/order', $config);
    }

    public function getSsl(int $id): array
    {
        return $this->request('get', "/ssl/{$id}");
    }

    public function updateSslInfo(int $id, array $data): array
    {
        return $this->request('post', "/ssl/{$id}", $data);
    }

    public function cancelSsl(int $id): array
    {
        return $this->request('delete', "/ssl/{$id}");
    }

    public function getSslInvoices(int $id): array
    {
        return $this->request('get', "/ssl/{$id}/invoices");
    }

    public function getSslWelcomeEmail(int $id): array
    {
        return $this->request('get', "/ssl/{$id}/welcome_email");
    }

    // ===========================================================================
    // Quick Servers (QS)
    // ===========================================================================

    public function listQs(): array
    {
        $result = $this->request('get', '/qs');
        return is_array($result) && ! ($result['error'] ?? false) ? $result : [];
    }

    public function getQsOrderCatalog(): array
    {
        return $this->request('get', '/qs/order');
    }

    public function quoteQsOrder(array $config): array
    {
        return $this->request('put', '/qs/order', $config);
    }

    public function placeQsOrder(array $config): array
    {
        return $this->request('post', '/qs/order', $config);
    }

    public function getQs(int $id): array
    {
        return $this->request('get', "/qs/{$id}");
    }

    public function updateQsInfo(int $id, array $data): array
    {
        return $this->request('post', "/qs/{$id}", $data);
    }

    public function cancelQs(int $id): array
    {
        return $this->request('delete', "/qs/{$id}");
    }

    public function startQs(int $id): array
    {
        return $this->request('get', "/qs/{$id}/start");
    }

    public function stopQs(int $id): array
    {
        return $this->request('get', "/qs/{$id}/stop");
    }

    public function restartQs(int $id): array
    {
        return $this->request('get', "/qs/{$id}/restart");
    }

    public function getQsBackup(int $id): array
    {
        return $this->request('get', "/qs/{$id}/backup");
    }

    public function getQsBackups(int $id): array
    {
        return $this->request('get', "/qs/{$id}/backups");
    }

    public function deleteQsBackup(int $id, string $file): array
    {
        return $this->request('delete', "/qs/{$id}/backups?" . http_build_query(['file' => $file]));
    }

    public function downloadQsBackup(int $id, string $file): array
    {
        return $this->request('patch', "/qs/{$id}/backups", ['file' => $file]);
    }

    public function restoreQs(int $id, string $backup, string $password): array
    {
        return $this->request('post', "/qs/{$id}/restore", ['backup' => $backup, 'password' => $password]);
    }

    public function blockSmtpQs(int $id): array
    {
        return $this->request('get', "/qs/{$id}/block_smtp");
    }

    public function getQsChangeHostname(int $id): array
    {
        return $this->request('get', "/qs/{$id}/change_hostname");
    }

    public function changeQsHostname(int $id, string $hostname): array
    {
        return $this->request('post', "/qs/{$id}/change_hostname", ['hostname' => $hostname]);
    }

    public function getQsChangeRootPasswordInfo(int $id): array
    {
        return $this->request('get', "/qs/{$id}/change_root_password");
    }

    public function changeQsRootPassword(int $id, string $password): array
    {
        return $this->request('post', "/qs/{$id}/change_root_password", ['password' => $password]);
    }

    public function getQsChangeTimezone(int $id): array
    {
        return $this->request('get', "/qs/{$id}/change_timezone");
    }

    public function changeQsTimezone(int $id, string $timezone): array
    {
        return $this->request('post', "/qs/{$id}/change_timezone", ['timezone' => $timezone]);
    }

    public function getQsChangeWebuzoPasswordInfo(int $id): array
    {
        return $this->request('get', "/qs/{$id}/change_webuzo_password");
    }

    public function changeQsWebuzoPassword(int $id, string $password): array
    {
        return $this->request('post', "/qs/{$id}/change_webuzo_password", ['password' => $password]);
    }

    public function disableQsCd(int $id): array
    {
        return $this->request('get', "/qs/{$id}/disable_cd");
    }

    public function ejectQsCd(int $id): array
    {
        return $this->request('get', "/qs/{$id}/eject_cd");
    }

    public function getQsInsertCd(int $id): array
    {
        return $this->request('get', "/qs/{$id}/insert_cd");
    }

    public function insertQsCd(int $id, string $url): array
    {
        return $this->request('post', "/qs/{$id}/insert_cd", ['url' => $url]);
    }

    public function disableQsQuota(int $id): array
    {
        return $this->request('get', "/qs/{$id}/disable_quota");
    }

    public function enableQsQuota(int $id): array
    {
        return $this->request('get', "/qs/{$id}/enable_quota");
    }

    public function getQsInvoices(int $id): array
    {
        return $this->request('get', "/qs/{$id}/invoices");
    }

    public function getQsReinstallTemplates(int $id): array
    {
        return $this->request('get', "/qs/{$id}/reinstall_os");
    }

    public function reinstallQsOs(int $id, string $template, string $localPassword, ?string $password = null): array
    {
        $payload = ['template' => $template, 'localPassword' => $localPassword];
        if ($password) {
            $payload['password'] = $password;
        }
        return $this->request('post', "/qs/{$id}/reinstall_os", $payload);
    }

    public function getQsResetPasswordInfo(int $id): array
    {
        return $this->request('get', "/qs/{$id}/reset_password");
    }

    public function resetQsPassword(int $id): array
    {
        return $this->request('post', "/qs/{$id}/reset_password");
    }

    public function getQsReverseDns(int $id): array
    {
        return $this->request('get', "/qs/{$id}/reverse_dns");
    }

    public function setQsReverseDns(int $id, array $ips): array
    {
        return $this->request('post', "/qs/{$id}/reverse_dns", ['ips' => $ips]);
    }

    public function getQsSetupVnc(int $id): array
    {
        return $this->request('get', "/qs/{$id}/setup_vnc");
    }

    public function setupQsVnc(int $id, string $vnc): array
    {
        return $this->request('post', "/qs/{$id}/setup_vnc", ['vnc' => $vnc]);
    }

    public function getQsTrafficUsage(int $id): array
    {
        return $this->request('get', "/qs/{$id}/traffic_usage");
    }

    public function getQsTrafficUsageFiltered(int $id, array $filters): array
    {
        return $this->request('post', "/qs/{$id}/traffic_usage", $filters);
    }

    public function getQsViewDesktop(int $id): array
    {
        return $this->request('get', "/qs/{$id}/view_desktop");
    }

    public function refreshQsViewDesktop(int $id): array
    {
        return $this->request('post', "/qs/{$id}/view_desktop");
    }

    public function getQsWelcomeEmail(int $id): array
    {
        return $this->request('get', "/qs/{$id}/welcome_email");
    }

    // ===========================================================================
    // Domains
    // ===========================================================================

    public function listDomains(): array
    {
        $result = $this->request('get', '/domains');
        return is_array($result) && ! ($result['error'] ?? false) ? $result : [];
    }

    public function lookupDomain(string $name): array
    {
        return $this->request('get', '/domains/lookup/' . rawurlencode($name));
    }

    public function searchDomain(string $name): array
    {
        return $this->request('get', '/domains/search/' . rawurlencode($name));
    }

    public function searchDomainFull(string $name, array $data = []): array
    {
        return $this->request('post', '/domains/search/' . rawurlencode($name), $data);
    }

    public function getDomainOrderCatalog(): array
    {
        return $this->request('get', '/domains/order');
    }

    public function previewDomainOrderFields(array $data): array
    {
        return $this->request('put', '/domains/order', $data);
    }

    public function validateDomainOrder(array $data): array
    {
        return $this->request('patch', '/domains/order', $data);
    }

    public function placeDomainOrder(array $data): array
    {
        return $this->request('post', '/domains/order', $data);
    }

    public function getDomain(int $id): array
    {
        return $this->request('get', "/domains/{$id}");
    }

    public function updateDomainInfo(int $id, array $data): array
    {
        return $this->request('post', "/domains/{$id}", $data);
    }

    public function cancelDomain(int $id): array
    {
        return $this->request('delete', "/domains/{$id}");
    }

    public function getDomainContact(int $id): array
    {
        return $this->request('get', "/domains/{$id}/contact");
    }

    public function updateDomainContact(int $id, array $data): array
    {
        return $this->request('post', "/domains/{$id}/contact", $data);
    }

    public function getDomainDnssec(int $id): array
    {
        return $this->request('get', "/domains/{$id}/dnssec");
    }

    public function addDomainDnssec(int $id, array $data): array
    {
        return $this->request('post', "/domains/{$id}/dnssec", $data);
    }

    public function deleteDomainDnssec(int $id): array
    {
        return $this->request('delete', "/domains/{$id}/dnssec");
    }

    public function getDomainInvoices(int $id): array
    {
        return $this->request('get', "/domains/{$id}/invoices");
    }

    public function getDomainRenewInfo(int $id): array
    {
        return $this->request('get', "/domains/{$id}/renew");
    }

    public function renewDomain(int $id, array $data = []): array
    {
        return $this->request('post', "/domains/{$id}/renew", $data);
    }

    public function getDomainTransferInfo(int $id): array
    {
        return $this->request('get', "/domains/{$id}/transfer");
    }

    public function transferDomain(int $id, array $data = []): array
    {
        return $this->request('post', "/domains/{$id}/transfer", $data);
    }

    public function getDomainWelcomeEmail(int $id): array
    {
        return $this->request('get', "/domains/{$id}/welcome_email");
    }

    public function getDomainWhois(int $id): array
    {
        return $this->request('get', "/domains/{$id}/whois");
    }

    public function setDomainWhois(int $id, array $data): array
    {
        return $this->request('post', "/domains/{$id}/whois", $data);
    }

    public function getDomainNameservers(int $id): array
    {
        return $this->request('get', "/domains/{$id}/nameservers");
    }

    public function quoteDomainNameservers(int $id, array $nameservers): array
    {
        return $this->request('put', "/domains/{$id}/nameservers", ['nameserver' => $nameservers]);
    }

    public function addDomainNameserver(int $id, string $name, string $ipAddress): array
    {
        return $this->request('post', "/domains/{$id}/nameservers", ['name' => $name, 'ipAddress' => $ipAddress]);
    }

    public function deleteDomainNameserver(int $id, array $data = []): array
    {
        return $this->request('delete', "/domains/{$id}/nameservers", $data);
    }
}
