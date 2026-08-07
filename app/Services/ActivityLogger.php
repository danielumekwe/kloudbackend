<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Single write path for the activity log that backs both the admin audit log
 * and the customer-facing activity timeline (Feature 2 + Feature 7) — resolves
 * "who did this" from the current session so callers never have to pass it.
 */
class ActivityLogger
{
    public function log(
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        bool $visibleToClient = true,
    ): ActivityLog {
        [$causerType, $causerId] = $this->resolveCauser();

        return ActivityLog::create([
            'subject_type'      => $subject?->getMorphClass(),
            'subject_id'        => $subject?->id,
            'causer_type'       => $causerType,
            'causer_id'         => $causerId,
            'action'            => $action,
            'description'       => $description,
            'properties'        => $properties,
            'visible_to_client' => $visibleToClient,
        ]);
    }

    /** @return array{0: string, 1: int|null} */
    private function resolveCauser(): array
    {
        if (session('isAdmin') && session('adminId')) {
            return ['admin', (int) session('adminId')];
        }

        if (session('clientId')) {
            return ['client', (int) session('clientId')];
        }

        return ['system', null];
    }
}
