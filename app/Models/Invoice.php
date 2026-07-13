<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'client_id',
        'status',
        'currency_code',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'payment_method',
        'paid_at',
    ];

    protected $casts = [
        'subtotal'   => 'decimal:2',
        'tax_rate'   => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total'      => 'decimal:2',
        'paid_at'    => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
