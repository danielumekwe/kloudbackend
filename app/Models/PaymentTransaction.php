<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'client_id',
        'whmcs_invoice_id',
        'gateway',
        'gateway_reference',
        'amount',
        'currency',
        'status',
        'raw_payload',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'raw_payload' => 'array',
    ];
}
