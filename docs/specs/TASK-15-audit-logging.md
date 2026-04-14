# TASK-15: Audit Logging

## Problem Statement

The app has no record of who did what. Once a form is published, a submission is approved, or a team member is removed, there's no way to answer *who*, *when*, or *from where*. That's a blocker for compliance (HIPAA, GDPR), post-incident investigation, and basic accountability for multi-tenant SaaS use.

## Goals

1. Provide an append-only audit trail of high-signal actions across the app
2. Capture actor (user), resource (type + id), action, IP address, user agent, and structured details
3. Make logging fire-and-forget from the caller's perspective — writes happen on a queue so the request path stays fast
4. Expose a read-only audit trail browser in the Filament panel for tenants with the `audit:view` permission

## Non-Goals

- **User-triggered editing/deletion** — logs are append-only. No UI for editing or deleting entries. DB retention is handled separately (future: TASK-33 compliance modes).
- **Tamper-proofing / signing** — no cryptographic chain. Relies on append-only model discipline, not cryptographic integrity.
- **External log sinks** — no shipping to S3/Datadog/Splunk. DB-only in this task.
- **Diff storage of attribute changes** — `details` captures whatever the caller passes, but we don't auto-diff every model change. That's scope creep.
- **Exporting audit logs** — view-only in this task. Export can come with or after TASK-22.

## Schema Changes

New `audit_logs` table:

| Column | Type | Default | Notes |
|---|---|---|---|
| `id` | uuid | — | Primary key |
| `team_id` | uuid | null | Nullable — some events (failed login, system) have no tenant context |
| `user_id` | uuid | null | Nullable — unauthenticated events (failed login) have no user |
| `resource_type` | string(100) | null | e.g. `form`, `submission`, `membership`, `team` |
| `resource_id` | string(100) | null | UUID or other identifier of the resource |
| `action` | string(100) | — | Stable enum-backed verb (e.g. `form.published`, `submission.status_changed`) |
| `ip_address` | string(45) | null | IPv4/IPv6 from `request()->ip()` |
| `user_agent` | text | null | From `request()->userAgent()` |
| `details` | json | null | Structured context (old/new values, reason, etc.) |
| `created_at` | timestamp | — | No `updated_at` — append-only |

Indexes: `team_id`, `user_id`, `resource_type + resource_id` composite, `action`, `created_at`.

Foreign keys: `team_id` and `user_id` both `ON DELETE SET NULL` so deleting a tenant or user doesn't blow away history.

## AuditAction Enum

Stable string values for `action` so UI filtering and downstream consumers can rely on them.

Groups covered in v1:

- `form.created`, `form.updated`, `form.deleted`, `form.published`, `form.unpublished`
- `submission.viewed`, `submission.status_changed`, `submission.reviewer_assigned`, `submission.deleted`, `submission.exported`
- `member.invited`, `member.role_changed`, `member.removed`, `invitation.cancelled`
- `team.updated`, `team.deleted`
- `auth.login`, `auth.login_failed`, `auth.logout`

Each case exposes `label()` for display and `color()` for the badge.

## AuditService

A single class `App\Services\AuditService` with one static method:

```php
AuditService::log(
    AuditAction $action,
    ?Model $resource = null,
    array $details = [],
    ?User $actor = null,
    ?Team $team = null,
): void
```

Behaviour:

- Resolves actor from `auth()->user()` if not passed, team from `Filament::getTenant()` if not passed
- Captures IP + user agent from the current request
- Dispatches a `WriteAuditLog` queued job with the payload
- Never throws — logging is best-effort. Exceptions during serialization are swallowed and reported via `report()`

## WriteAuditLog Job

Queued job on the default connection. Writes the `AuditLog` row. Uses `ShouldBeUnique` is not needed — duplicate logs are fine (they're distinct events even if identical).

## Instrumentation

| Action | Where | Details captured |
|---|---|---|
| `form.created` | Form model `created` observer | name |
| `form.updated` | Form model `updated` observer | changed attribute keys |
| `form.deleted` | Form model `deleted` observer | name |
| `form.published` / `unpublished` | Form observer when `is_published` changes | previous value |
| `submission.viewed` | `ViewSubmission::mount` | none |
| `submission.status_changed` | `ViewSubmission` status action | old + new status |
| `submission.reviewer_assigned` | `ViewSubmission` assign action | old + new reviewer id |
| `submission.deleted` | Submission observer | form id |
| `submission.exported` | `ProcessBulkExport::handle` | reason, filter summary, row count |
| `member.invited` / `role_changed` / `removed` | TeamSettings page | affected user id, role(s) |
| `team.updated` / `deleted` | Team observer | changed attribute keys |
| `auth.login` / `login_failed` / `logout` | Fortify event listeners | email on failed, user id otherwise |

Observers are registered via `AppServiceProvider::boot()` or `Model::observe()` calls on the models.

## Audit Log Browser Page

A new Filament custom page `App\Filament\Pages\AuditLogs` (tenant-scoped — only shows entries where `team_id` matches the current tenant, plus null-team events attributable to members of this tenant).

- Read-only list (no actions)
- Columns: When, Actor, Action (badge), Resource, IP
- Filters: action (select), actor (select across team members), date range, resource type
- Pagination, default sort by `created_at desc`
- `canAccess()` gated on `TeamPermission::ViewAudit`

## Requirements

### Must-Have (P0)

- [ ] `audit_logs` migration with all columns and indexes
- [ ] `AuditLog` model with HasUuids, casts, and BelongsTo relationships for actor and team
- [ ] `AuditAction` enum covering all v1 actions with labels and colors
- [ ] `AuditService::log()` static helper
- [ ] `WriteAuditLog` queued job
- [ ] Observers for Form, Submission, Team, Membership
- [ ] Login/logout/failed login listeners
- [ ] Instrumentation on `ViewSubmission` page actions
- [ ] `AuditLogs` Filament page, gated by `audit:view` permission, tenant-scoped
- [ ] Tests for service, job, observers, and page access

### Nice-to-Have (P1)

- [ ] CSV export of audit log entries (likely merged with TASK-22 export infrastructure)

## Files to Create/Modify

- New: `app/Models/AuditLog.php`
- New: `app/Enums/AuditAction.php`
- New: `app/Services/AuditService.php`
- New: `app/Jobs/WriteAuditLog.php`
- New: `app/Observers/FormObserver.php`, `SubmissionObserver.php`, `TeamObserver.php`, `MembershipObserver.php`
- New: `app/Listeners/AuditAuthEvents.php` (handles Login, Logout, Failed)
- New: migration for `audit_logs` table
- New: `database/factories/AuditLogFactory.php`
- New: `app/Filament/Pages/AuditLogs.php`
- New: `resources/views/filament/pages/audit-logs.blade.php`
- Modify: `app/Providers/AppServiceProvider.php` — register observers + auth listeners
- Modify: `app/Filament/Resources/Submissions/Pages/ViewSubmission.php` — add log calls
- New: `tests/Feature/AuditLogs/AuditLogTest.php`
