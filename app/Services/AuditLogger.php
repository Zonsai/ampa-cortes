<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AuditLogger
{
    /**
     * Records an auditable action.
     *
     * Safe in CLI/tests: actor and request data are optional. Logging never
     * breaks the calling action in production (failures are logged, not thrown);
     * in tests it rethrows so real bugs are not hidden.
     *
     * @param  array<string, mixed>  $properties  before/after snapshots and metadata
     */
    public function log(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        array $properties = [],
        ?User $actor = null,
        ?string $subjectLabel = null,
    ): ?AuditLog {
        try {
            $actor ??= auth()->user();
            $request = request();

            return AuditLog::create([
                'actor_id' => $actor?->id,
                'actor_name' => $actor?->name,
                'actor_email' => $actor?->email,
                'action' => $action,
                'description' => $description,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject?->getKey(),
                'subject_label' => $subjectLabel ?? $this->resolveLabel($subject),
                'properties' => $properties !== [] ? $properties : null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ]);
        } catch (\Throwable $e) {
            // Surface real bugs in tests; never break the main action in production.
            if (app()->runningUnitTests()) {
                throw $e;
            }

            Log::error('AuditLogger failed to record action', [
                'action' => $action,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function resolveLabel(?Model $subject): ?string
    {
        if ($subject === null) {
            return null;
        }

        foreach (['full_name', 'name', 'title', 'label'] as $attribute) {
            $value = $subject->getAttribute($attribute);
            if (filled($value)) {
                return (string) $value;
            }
        }

        return class_basename($subject).' #'.$subject->getKey();
    }
}
