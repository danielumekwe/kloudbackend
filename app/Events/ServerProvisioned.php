<?php

namespace App\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * $order is a VpsOrder or DedicatedServerOrder that just transitioned to 'provisioned'.
 */
class ServerProvisioned
{
    use Dispatchable;

    public function __construct(public Model $order)
    {
    }
}
