<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ServerInstance extends Model
{
    protected $fillable = [
        'orderable_type',
        'orderable_id',
        'client_id',
        'interserver_id',
        'hostname',
        'ipv4',
        'ipv6',
        'ssh_port',
        'root_username',
        'root_password_encrypted',
        'os',
        'cpu',
        'ram',
        'disk',
        'bandwidth',
        'location',
        'status',
        'provisioned_at',
        'renewal_at',
    ];

    protected $casts = [
        'provisioned_at' => 'datetime',
        'renewal_at'     => 'datetime',
    ];

    protected $hidden = ['root_password_encrypted'];

    public function orderable()
    {
        return $this->morphTo();
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function decryptedRootPassword(): ?string
    {
        if (! $this->root_password_encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($this->root_password_encrypted);
        } catch (\Exception) {
            return null;
        }
    }
}
