# TASK-12: Expand RBAC to Five Roles

## Problem Statement

The current RBAC system has three roles (Owner, Admin, Member) which is too coarse for the collaboration model described in the FormForge Technical Architecture v1.0. Teams need finer-grained access control — an Editor who can build forms but not manage the team, a Reviewer who can review submissions but not edit forms, and a Viewer with read-only access.

## Goals

1. Add Editor, Reviewer, and Viewer roles to the TeamRole enum
2. Implement the full permission matrix from Architecture Doc Section 4.2
3. Enforce permissions across all existing policies and Filament UI
4. Maintain backward compatibility — existing Owner/Admin/Member assignments continue to work

## Non-Goals

- **Billing management** — TASK-35 (plan enforcement) will implement billing. We add the permission entry now but it won't be enforced until that feature exists.
- **Audit trail access** — TASK-15 will implement audit logging. Permission entry added now, enforcement deferred.
- **Integration configuration** — TASK-29/30 will implement API/webhooks. Permission entry added now, enforcement deferred.
- **Submission approval/rejection workflow** — TASK-20 will implement this. Permission entry added now, enforcement deferred.
- **Submission notes** — TASK-21 will implement this. Permission entry added now, enforcement deferred.
- **Submission export** — TASK-22 will implement this. Permission entry added now, enforcement deferred.
- **"Own forms" scoping for submissions** — The architecture doc distinguishes "View Submissions (own forms)" vs "View All Submissions (workspace)". Currently there is no concept of form ownership (forms belong to projects, not users). We will treat `submission:view` as workspace-wide for now and introduce ownership scoping when the submission management tasks land.

## Permission Matrix

Mapped from Architecture Doc Section 4.2 to the current permission system:

| Permission | Owner | Admin | Editor | Reviewer | Viewer |
|---|---|---|---|---|---|
| `team:update` | ✓ | ✓ | | | |
| `team:delete` | ✓ | | | | |
| `member:add` | ✓ | ✓ | | | |
| `member:update` | ✓ | ✓ | | | |
| `member:remove` | ✓ | ✓ | | | |
| `invitation:create` | ✓ | ✓ | | | |
| `invitation:cancel` | ✓ | ✓ | | | |
| `project:create` | ✓ | ✓ | ✓ | | |
| `project:update` | ✓ | ✓ | ✓ | | |
| `project:delete` | ✓ | ✓ | | | |
| `form:create` | ✓ | ✓ | ✓ | | |
| `form:update` | ✓ | ✓ | ✓ | | |
| `form:delete` | ✓ | ✓ | | | |
| `form:publish` | ✓ | ✓ | ✓ | | |
| `submission:view` | ✓ | ✓ | ✓ | ✓ | ✓ |
| `submission:delete` | ✓ | ✓ | | | |
| `submission:export` | ✓ | ✓ | ✓ | | |
| `submission:review` | ✓ | ✓ | ✓ | ✓ | |
| `billing:manage` | ✓ | | | | |
| `audit:view` | ✓ | ✓ | | | |
| `integration:manage` | ✓ | ✓ | ✓ | | |

### Key Decisions

- **Member → removed**: The old "Member" role is replaced by the more descriptive Editor/Reviewer/Viewer roles. Existing `member` role values in the database will be migrated to `editor` (closest equivalent since Member had only `submission:view`, but Editor is the natural "team contributor" role).
- **Admin gains member management**: Currently only Owner can manage members. The architecture doc gives Admin the same capability, which makes more sense for team delegation.
- **Editor gets project:create/update but not project:delete**: Editors can organise their work but can't remove project structures.
- **Editor gets form:publish**: The architecture doc grants Editors publish/unpublish, matching the "form builder" persona.
- **New permissions added**: `form:publish`, `submission:export`, `submission:review`, `billing:manage`, `audit:view`, `integration:manage` — these are defined in the enum now but only `form:publish` will have immediate enforcement (in FormPolicy). The rest are placeholders for future tasks.

## Role Hierarchy

| Role | Level | Description |
|---|---|---|
| Owner | 5 | Full access. Manages billing. One per team (enforced). |
| Admin | 4 | Full access except billing. Manages team members and settings. |
| Editor | 3 | Builds and publishes forms. Views and exports submissions. |
| Reviewer | 2 | Reviews and acts on submissions. Read-only for forms. |
| Viewer | 1 | Read-only access to submissions. |

## Assignable Roles

`TeamRole::assignable()` will return `[Admin, Editor, Reviewer, Viewer]` — Owner remains non-assignable (transferred, not granted).

## Requirements

### Must-Have (P0)

- [ ] Add `Editor`, `Reviewer`, `Viewer` cases to `TeamRole` enum
- [ ] Add new permission cases to `TeamPermission` enum
- [ ] Update `TeamRole::permissions()` to return the correct permission set for each role
- [ ] Update `TeamRole::level()` with the five-tier hierarchy
- [ ] Update `TeamRole::assignable()` to include Editor, Reviewer, Viewer
- [ ] Write migration to convert existing `member` role values to `editor`
- [ ] Update all policies (Team, Project, Form, Submission) for new permissions
- [ ] Update TeamSettings page role selection to show all assignable roles
- [ ] Update DatabaseSeeder to use new role values
- [ ] Update all existing tests and add new tests for expanded permission matrix

### Nice-to-Have (P1)

- [ ] Add `form:publish` enforcement in FormPolicy (separate from `form:update`)
- [ ] Add role description/label methods for better UI display

## Files to Modify

- `app/Enums/TeamRole.php`
- `app/Enums/TeamPermission.php`
- `app/Policies/TeamPolicy.php`
- `app/Policies/ProjectPolicy.php`
- `app/Policies/FormPolicy.php`
- `app/Policies/SubmissionPolicy.php`
- `app/Filament/Pages/TeamSettings.php`
- `database/seeders/DatabaseSeeder.php`
- New migration for `member` → `editor` data migration
- `tests/Feature/Teams/TeamMemberTest.php`
- `tests/Feature/Teams/TeamTest.php`
- Other test files that reference TeamRole
