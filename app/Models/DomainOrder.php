<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainOrder extends Model
{
    protected $fillable = [
        'client_id',
        'whmcs_invoice_id',
        'interserver_domain_id',
        'domain_name',
        'tld',
        'order_type',
        'registration_years',
        'status',
        'price',
        'whois_privacy',
        'registrant_contact',
        'config',
        'failure_reason',
    ];

    protected $casts = [
        'registrant_contact' => 'array',
        'config'             => 'array',
        'whois_privacy'      => 'boolean',
        'price'              => 'decimal:2',
    ];

    public function renewals()
    {
        return $this->hasMany(DomainRenewal::class);
    }
}
