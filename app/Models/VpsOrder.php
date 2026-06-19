<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VpsOrder extends Model
{
    protected $fillable = [
        'client_id',
        'category',
        'whmcs_invoice_id',
        'interserver_vps_id',
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
