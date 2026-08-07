<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerMetric extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'orderable_type',
        'orderable_id',
        'bandwidth_usage_gb',
        'disk_usage_gb',
        'cpu_usage_percent',
        'ram_usage_percent',
        'recorded_at',
    ];

    protected $casts = [
        'bandwidth_usage_gb' => 'decimal:2',
        'disk_usage_gb'      => 'decimal:2',
        'cpu_usage_percent'  => 'decimal:2',
        'ram_usage_percent'  => 'decimal:2',
        'recorded_at'        => 'datetime',
    ];

    public function orderable()
    {
        return $this->morphTo();
    }
}
