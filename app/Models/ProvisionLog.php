<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProvisionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'orderable_type',
        'orderable_id',
        'attempt',
        'status',
        'request_payload',
        'response_payload',
        'error_message',
    ];

    protected $casts = [
        'request_payload'  => 'array',
        'response_payload' => 'array',
        'created_at'       => 'datetime',
    ];

    public function orderable()
    {
        return $this->morphTo();
    }
}
