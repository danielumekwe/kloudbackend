<?php

namespace App\Enums;

enum AdminRole: string
{
    case SuperAdmin = 'super_admin';
    case FinanceManager = 'finance_manager';
    case SupportAgent = 'support_agent';
    case SalesManager = 'sales_manager';
    case VpsManager = 'vps_manager';
    case DomainManager = 'domain_manager';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::FinanceManager => 'Finance Manager',
            self::SupportAgent => 'Support Agent',
            self::SalesManager => 'Sales Manager',
            self::VpsManager => 'VPS Manager',
            self::DomainManager => 'Domain Manager',
        };
    }

    /**
     * Only super_admin can manage other admin accounts — granting yourself or
     * anyone else admin-management rights from a non-super role would let that
     * role escalate itself to super_admin.
     */
    public function canManageAdmins(): bool
    {
        return $this === self::SuperAdmin;
    }

    public function canManagePricing(): bool
    {
        return in_array($this, [self::SuperAdmin, self::FinanceManager], true);
    }

    public function canManageTickets(): bool
    {
        return in_array($this, [self::SuperAdmin, self::SupportAgent], true);
    }
}
