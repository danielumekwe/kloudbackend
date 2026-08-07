<?php

namespace App\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * $order is a VpsOrder or DedicatedServerOrder whose final provisioning attempt failed.
 */
class ServerProvisioningFailed
{
    use Dispatchable;

    public function __construct(public Model $order, public string $reason)
    {
    }
}
