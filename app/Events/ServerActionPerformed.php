<?php

namespace App\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Generic "something happened to this server" event — fired by admin and
 * customer lifecycle actions alike (start/stop/restart/password reset/
 * suspend/cancel/etc.) so activity logging lives in one listener instead of
 * being duplicated at every controller call site.
 */
class ServerActionPerformed
{
    use Dispatchable;

    public function __construct(
        public Model $order,
        public string $action,
        public string $description,
        public array $properties = [],
        public bool $visibleToClient = true,
    ) {
    }
}
