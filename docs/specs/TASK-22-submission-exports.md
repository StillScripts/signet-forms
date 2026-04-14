# TASK-22: CSV/Excel Export of Submissions

## Problem Statement

Users who collect form data need to get it out of the app — for reporting, spreadsheets, sharing with non-users, and backup. Running synchronously in a web request doesn't work: forms with thousands of submissions blow memory, hit timeouts, and tie up the UI. We need a queued, streaming export that delivers via email.

## Goals

1. Let permitted users export a form's submissions as CSV, filtered by status and date range
2. Run the export on a queue so it never blocks the request or hits PHP timeouts
3. Stream rows to disk rather than buffering in memory, so multi-GB exports don't OOM
4. Deliver a signed, expiring download link via email once the file is ready
5. Require an export reason that gets written to the audit trail (TASK-15)

## Non-Goals

- **Excel (.xlsx) output** — CSV only for v1. xlsx needs a third-party lib (phpoffice/phpspreadsheet) and adds packaging/compression overhead; defer to a follow-up task.
- **Cross-form exports in one file** — one export job targets a single form. Schema differences between forms make merged exports ambiguous.
- **In-app download history search / restore** — exports are fire-and-forget; the record is retained for status tracking and the download link, nothing more.
- **Automatic scheduled/recurring exports** — on-demand only in v1.
- **Field-level masking for encrypted values** — TASK-34 (field encryption) isn't built yet; revisit when it lands.

## Schema Changes

New `submission_exports` table:

| Column | Type | Default | Notes |
|---|---|---|---|
| `id` | uuid | — | Primary key |
| `team_id` | uuid | — | Tenant scope |
| `form_id` | uuid | — | Source form |
| `requested_by` | uuid | — | User who triggered the export |
| `status` | string(20) | `'pending'` | Enum: pending, processing, completed, failed |
| `format` | string(10) | `'csv'` | Future-proofing; only `csv` in v1 |
| `filters` | json | null | Serialised filter payload (status, date range) |
| `reason` | text | — | Required export reason (for audit) |
| `row_count` | integer | null | Populated on completion |
| `file_path` | string(255) | null | Storage path once written |
| `file_size` | integer | null | Bytes, populated on completion |
| `error_message` | text | null | On failure |
| `expires_at` | timestamp | — | `completed_at + 7 days`, clamped to a max |
| `completed_at` | timestamp | null | — |
| `failed_at` | timestamp | null | — |
| `created_at` / `updated_at` | timestamp | — | — |

Indexes: `team_id`, `form_id`, `requested_by`, composite `(team_id, created_at)`, `expires_at`.

FKs cascade-delete on team, form, user.

## Enums

### `SubmissionExportStatus`

- `Pending` — queued, not started
- `Processing` — worker is writing
- `Completed` — file available, link sent
- `Failed` — error captured

Each exposes `label()` and `color()`.

## ProcessBulkExport Job

Queued job on the default connection.

Steps:

1. Mark export as `processing`
2. Open a stream to `local` disk at `exports/{team_id}/{export_id}.csv`
3. Chunk submissions in batches of 500 using `cursor()` — never loads all into memory
4. Write headers: metadata columns (id, submitted_at, status, respondent name/email, IP) + flattened field keys from the form schema
5. On each row, merge field values from `data` JSON into the metadata columns
6. Update `row_count`, `file_size`, `completed_at`, `expires_at`, `status = completed`
7. Dispatch `SubmissionExportReady` mailable to the requester with a signed download URL
8. Fire `AuditAction::SubmissionExported` via `AuditService`
9. On exception: mark `failed`, store `error_message`, rethrow so the job retries

Retries: 3 times with exponential backoff (1m, 5m, 15m).

## Download Route

Signed, named route: `exports.download`. Controller:

1. Validates the signed URL (built-in `signed` middleware)
2. Looks up the export
3. Aborts 403 if the current user isn't the requester AND doesn't have `audit:view` permission on the export's team
4. Aborts 410 Gone if past `expires_at` or file missing
5. Returns `Storage::download($export->file_path, "submissions-{$form->slug}.csv")`

## Export UI

New action on `SubmissionsTable` toolbar: `Export Submissions`.

Visible when the current user has `submission:export` permission. Opens a modal:

- **Form** select (required) — tenant-scoped list
- **Status** select (multi, optional) — any of SubmissionStatus cases
- **Date from** / **Date to** — optional
- **Reason** textarea (required, min 10 chars) — captured in audit log

Submit:

1. Create a `SubmissionExport` row with `status=pending`, filters, reason
2. Dispatch `ProcessBulkExport`
3. Show a notification: "Export queued. We'll email you when it's ready."

### Export history list

A new Filament page `Exports` under the submissions area. Shows the requesting user's exports with status, row count, requested at, and a manual download link (for users still in-session). Read-only — no edit/delete.

## Email

`SubmissionExportReady` mailable. Plain subject + templated body with the signed download URL, form name, row count, and expiry timestamp.

## Cleanup

Scheduled command `exports:clean` runs daily, deletes files and rows where `expires_at < now()`. Registered in `app/Console/Kernel.php` or via `routes/console.php`.

## Requirements

### Must-Have (P0)

- [ ] `submission_exports` migration with all columns and indexes
- [ ] `SubmissionExport` model with HasUuids, relationships
- [ ] `SubmissionExportStatus` enum
- [ ] `ProcessBulkExport` queued job with streaming cursor-based write
- [ ] `SubmissionExportReady` mailable
- [ ] Signed download route and controller
- [ ] Export action on `SubmissionsTable` with filter + reason modal
- [ ] Permission gate on `submission:export`
- [ ] `AuditAction::SubmissionExported` integration
- [ ] Scheduled `exports:clean` command
- [ ] Tests: model, job, mailable, download route (happy + expired + forbidden), permission gate, cleanup command

### Nice-to-Have (P1)

- [ ] Export history page
- [ ] Excel (xlsx) format option

## Files to Create/Modify

- New: `app/Models/SubmissionExport.php`
- New: `app/Enums/SubmissionExportStatus.php`
- New: `app/Jobs/ProcessBulkExport.php`
- New: `app/Mail/SubmissionExportReady.php`
- New: `app/Http/Controllers/ExportDownloadController.php`
- New: `app/Console/Commands/CleanupExpiredExports.php`
- New: migration for `submission_exports` table
- New: `database/factories/SubmissionExportFactory.php`
- New: `resources/views/emails/submission-export-ready.blade.php`
- Modify: `app/Filament/Resources/Submissions/Tables/SubmissionsTable.php` — add Export action
- Modify: `routes/web.php` — add signed download route
- Modify: `routes/console.php` — register the cleanup schedule
- New: `tests/Feature/Submissions/SubmissionExportTest.php`
