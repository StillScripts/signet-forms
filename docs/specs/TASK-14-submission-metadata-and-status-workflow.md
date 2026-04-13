# TASK-14: Add Submission Metadata and Status Workflow

## Problem Statement

Submissions currently store only field data and timestamps. Administrators need richer context about each submission (how it was submitted, by whom, which form version) and the ability to track submissions through a review workflow (pending → approved/rejected/archived).

## Goals

1. Capture submission metadata (IP, user agent, device, referrer, time_to_complete) on public form submit
2. Track which form schema version a submission was made against
3. Add a status workflow with five states for submission review
4. Allow assigning a reviewer to a submission
5. Capture respondent identity (email, name) when available from form data
6. Surface all new fields in the admin panel's existing submission views

## Non-Goals

- **Full approval workflow engine** — TASK-20 will build multi-stage workflows with SLA enforcement. This task adds the basic status field and manual transitions.
- **Email notifications on status change** — TASK-24 will handle notification rules.
- **Submission notes/comments** — TASK-21 will add internal notes.
- **Time-to-complete tracking** — Requires client-side JS timer. We add the metadata column now but populate it as `null` until a future task adds the JS instrumentation.

## Schema Changes

New columns on `submissions` table:

| Column | Type | Default | Notes |
|---|---|---|---|
| `status` | string | `'pending'` | Cast to SubmissionStatus enum |
| `metadata` | json | `null` | IP, user agent, device, referrer, time_to_complete |
| `form_version` | unsignedInteger | `null` | Version number from form_versions at time of submit |
| `assigned_reviewer_id` | foreignUuid | `null` | FK to users, nullOnDelete |
| `respondent_email` | string(255) | `null` | Extracted from submission data if an email field exists |
| `respondent_name` | string(255) | `null` | Extracted from submission data if a name field exists |

## SubmissionStatus Enum

```
pending → in_review → approved
                   → rejected
pending → archived
in_review → archived
approved → archived
rejected → archived
```

All statuses: `pending`, `in_review`, `approved`, `rejected`, `archived`

Valid transitions:
- `pending`: → `in_review`, `archived`
- `in_review`: → `approved`, `rejected`, `archived`
- `approved`: → `archived`
- `rejected`: → `archived`
- `archived`: (terminal)

## Metadata Capture

On `PublicFormPage::submit()`, capture:

```php
'metadata' => [
    'ip_address' => request()->ip(),
    'user_agent' => request()->userAgent(),
    'referer' => request()->header('referer'),
]
```

## Respondent Identity Extraction

After collecting form data, scan for common field keys to extract respondent info:
- **Email**: Look for field key `email` in submission data
- **Name**: Look for field key `name`, or concatenate `first_name` + `last_name` if present

## Admin Panel Changes

### ViewSubmission Page

Add to the existing infolist:
- **Status badge** with colour coding (pending=gray, in_review=warning, approved=success, rejected=danger, archived=gray)
- **Respondent section** showing email and name when present
- **Metadata section** showing IP, user agent, referer
- **Form version** number
- **Assigned reviewer** name

### ViewSubmission Header Actions

- **Change Status** action — Select from valid transitions for current status. Requires `submission:review` permission.
- **Assign Reviewer** action — Select a team member. Requires `submission:review` permission.

### SubmissionsTable (List View)

Add columns:
- **Status** badge column
- **Respondent** column (email or name)

Add filter:
- **Status** filter (select)

## Requirements

### Must-Have (P0)

- [ ] Migration adding all six columns to submissions table
- [ ] SubmissionStatus enum with transition validation
- [ ] Metadata capture in PublicFormPage::submit()
- [ ] Form version capture on submit
- [ ] Respondent identity extraction on submit
- [ ] Status badge and transition actions on ViewSubmission page
- [ ] Reviewer assignment action on ViewSubmission page
- [ ] Status column and filter on submissions list
- [ ] Update DatabaseSeeder with sample status/metadata data
- [ ] Tests for status transitions, metadata capture, permission gates

### Nice-to-Have (P1)

- [ ] Respondent column on submissions list table

## Files to Modify

- New: `app/Enums/SubmissionStatus.php`
- New: migration for submissions table columns
- `app/Models/Submission.php` — new casts, relationships, methods
- `app/Livewire/PublicFormPage.php` — metadata and respondent capture
- `app/Filament/Resources/Submissions/Pages/ViewSubmission.php` — infolist + actions
- `app/Filament/Resources/Submissions/Tables/SubmissionsTable.php` — new columns/filters
- `app/Policies/SubmissionPolicy.php` — review gate
- `database/seeders/DatabaseSeeder.php` — sample data
- `tests/Feature/Submissions/SubmissionTest.php` — updated and new tests
