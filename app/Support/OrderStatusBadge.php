<?php

namespace App\Support;

/**
 * Single source of truth for order-status label/color across admin and
 * customer views — replaces the ad-hoc per-view $badgeClass match blocks
 * that previously existed in each Blade file separately.
 */
class OrderStatusBadge
{
    public static function label(string $status): string
    {
        return match ($status) {
            'pending_payment' => 'Pending Payment',
            'paid'             => 'Paid',
            'provisioning'     => 'Provisioning',
            'provisioned'      => 'Active',
            'suspended'        => 'Suspended',
            'failed'           => 'Provisioning Failed',
            'cancelled'        => 'Cancelled',
            'terminated'       => 'Terminated',
            default            => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    /**
     * Tailwind classes matching the existing badge-* utility classes used
     * throughout the admin/customer layouts (see resources/css/app.css).
     */
    public static function classes(string $status): string
    {
        return match ($status) {
            'provisioned'                  => 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400',
            'paid'                         => 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
            'pending_payment'              => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
            'provisioning'                 => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400',
            'suspended'                    => 'bg-orange-100 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400',
            'failed'                       => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400',
            'cancelled', 'terminated'      => 'bg-slate-100 text-slate-600 dark:bg-white/[0.06] dark:text-slate-400',
            default                        => 'bg-slate-100 text-slate-600 dark:bg-white/[0.06] dark:text-slate-400',
        };
    }
}
