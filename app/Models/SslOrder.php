<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SslOrder extends Model
{
    protected $fillable = [
        'client_id',
        'whmcs_invoice_id',
        'interserver_ssl_id',
        'status',
        'price',
        'billing_cycle',
        'config',
        'failure_reason',
    ];

    protected $casts = [
        'config' => 'array',
        'price'  => 'decimal:2',
    ];
}
