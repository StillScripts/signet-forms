<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Jobs\WriteAuditLog;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

class AuditService
{
    /**
     * Record an audit trail entry. Fire-and-forget — never throws.
     *
     * @param  array<string, mixed>  $details
     */
    public static function log(
        AuditAction $action,
        ?Model $resource = null,
        array $details = [],
        ?User $actor = null,
        ?Team $team = null,
    ): void {
        try {
            $actor ??= auth()->user();
            $team ??= static::resolveTenant();

            $request = request();

            WriteAuditLog::dispatch(
                action: $action->value,
                teamId: $team?->getKey(),
                userId: $actor?->getKey(),
                resourceType: $resource ? static::resolveResourceType($resource) : null,
                resourceId: $resource?->getKey() ? (string) $resource->getKey() : null,
                ipAddress: $request?->ip(),
                userAgent: $request?->userAgent(),
                details: $details,
                occurredAt: now()->toDateTimeString(),
            );
        } catch (Throwable $e) {
            report($e);
        }
    }

    protected static function resolveTenant(): ?Team
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Team ? $tenant : null;
    }

    protected static function resolveResourceType(Model $resource): string
    {
        return match (class_basename($resource)) {
            'TeamInvitation' => 'invitation',
            default => Str::snake(class_basename($resource)),
        };
    }
}
