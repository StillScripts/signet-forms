<?php

namespace App\Jobs;

use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class WriteAuditLog implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public string $action,
        public ?string $teamId = null,
        public ?string $userId = null,
        public ?string $resourceType = null,
        public ?string $resourceId = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public array $details = [],
        public ?string $occurredAt = null,
    ) {}

    public function handle(): void
    {
        AuditLog::create([
            'team_id' => $this->teamId,
            'user_id' => $this->userId,
            'resource_type' => $this->resourceType,
            'resource_id' => $this->resourceId,
            'action' => $this->action,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'details' => $this->details ?: null,
            'created_at' => $this->occurredAt ?? now(),
        ]);
    }
}
