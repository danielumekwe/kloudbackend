<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'action',
        'description',
        'properties',
        'visible_to_client',
    ];

    protected $casts = [
        'properties'         => 'array',
        'visible_to_client'  => 'boolean',
        'created_at'         => 'datetime',
    ];

    public function subject()
    {
        return $this->morphTo();
    }

    public function causerName(): string
    {
        if ($this->causer_type === 'admin') {
            return optional(Admin::find($this->causer_id))->email ?? 'Admin';
        }

        if ($this->causer_type === 'client') {
            $client = Client::find($this->causer_id);
            return $client ? trim("{$client->firstname} {$client->lastname}") : 'Customer';
        }

        return 'System';
    }
}
