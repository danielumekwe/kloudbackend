<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $groups = [
            [
                'label' => 'Servers',
                'services' => [
                    ['name' => 'Linux VPS', 'description' => 'Self-managed Linux virtual servers.', 'route' => route('vps.catalog', 'linux-vps')],
                    ['name' => 'Managed VPS', 'description' => 'Fully managed virtual servers with control panel options.', 'route' => route('vps.catalog', 'managed-vps')],
                    ['name' => 'Storage VPS', 'description' => 'High-capacity storage-optimized virtual servers.', 'route' => route('vps.catalog', 'storage-vps')],
                    ['name' => 'Windows VPS', 'description' => 'Virtual servers running Windows Server.', 'route' => route('vps.catalog', 'windows-vps')],
                    ['name' => 'Quick Servers', 'description' => 'Pre-configured servers ready to deploy instantly.', 'route' => route('qs.catalog')],
                    ['name' => 'Dedicated Server', 'description' => 'Single-tenant physical servers.', 'route' => route('coming-soon', 'dedicated-server'), 'comingSoon' => true],
                    ['name' => 'Managed Dedicated Server', 'description' => 'Fully managed single-tenant physical servers.', 'route' => route('coming-soon', 'managed-dedicated-server'), 'comingSoon' => true],
                ],
            ],
            [
                'label' => 'Web & Security',
                'services' => [
                    ['name' => 'SSL Certificates', 'description' => 'Secure your domains with trusted SSL certificates.', 'route' => route('ssl.catalog')],
                    ['name' => 'Domain Registration', 'description' => 'Search and register new domain names.', 'route' => route('domains.search')],
                    ['name' => 'Backup & Security', 'description' => 'Automated backups and security hardening.', 'route' => route('coming-soon', 'backup-and-security'), 'comingSoon' => true],
                ],
            ],
            [
                'label' => 'Productivity',
                'services' => [
                    ['name' => 'Business Email Hosting', 'description' => 'Professional email hosting for your domain.', 'route' => route('coming-soon', 'business-email-hosting'), 'comingSoon' => true],
                ],
            ],
        ];

        return view('dashboard.servers.order', compact('groups'));
    }
}
