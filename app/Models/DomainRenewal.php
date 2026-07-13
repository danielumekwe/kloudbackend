<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainRenewal extends Model
{
    protected $fillable = [
        'domain_order_id',
        'invoice_id',
        'years',
        'price',
        'status',
        'config',
        'failure_reason',
    ];

    protected $casts = [
        'config' => 'array',
        'price'  => 'decimal:2',
    ];

    public function domainOrder()
    {
        return $this->belongsTo(DomainOrder::class);
    }
}
