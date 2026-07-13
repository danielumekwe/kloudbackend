<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QsOrder extends Model
{
    protected $fillable = [
        'client_id',
        'invoice_id',
        'interserver_qs_id',
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

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
