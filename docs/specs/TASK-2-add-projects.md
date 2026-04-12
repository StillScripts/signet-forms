# TASK-2: Add Projects to the Application

## Problem Statement

Signet Forms is a form builder for governance and security-sensitive organisations. The current data model supports Teams (multi-tenancy) but has no way to organise work within a team. As Forms are added in future tasks, users will need a container to group related forms — by client, department, engagement, or initiative. Without Projects, all forms would sit in a flat list under a team, making it hard to manage at scale.

Projects are the organisational layer between Teams and Forms: **Team → Project → Form**.

## Goals

1. **Introduce the Project model** as a first-class entity owned by a Team, with name, description, slug, and optional URL.
2. **Full CRUD via Filament** — team members can create, view, edit, and delete projects within their team's admin panel.
3. **Authorization** — Owners and Admins can create/edit/delete projects. Members can view projects. Follows the existing TeamRole/TeamPermission pattern.
4. **Slug auto-generation** — Slugs are derived from the project name automatically (reusing the `GeneratesUniqueTeamSlugs` pattern but scoped to the team).
5. **Test coverage** — Model, policy, and Filament resource are fully tested with Pest.

## Non-Goals

- **Forms** — The Form model and its relationship to Projects is a separate task (TASK-3+). Projects will have a `forms()` relationship stub but no Form model yet.
- **Project-level permissions** — No per-project access control. All team members can see all projects in their team. Fine-grained project permissions are a future consideration.
- **Status/workflow** — No active/archived/draft status. Projects are either present or soft-deleted.
- **Project settings page** — No dedicated settings or configuration beyond the Filament resource CRUD.
- **Public-facing pages** — Projects only exist in the Filament admin panel for now. No public routes.

## User Stories

**As a team Owner/Admin:**
- I want to create a project with a name and optional description so I can organise my team's upcoming forms.
- I want to optionally associate a project with a URL so I can link it to the client website or system the forms relate to.
- I want to edit a project's details after creation.
- I want to delete a project that is no longer needed.

**As a team Member:**
- I want to see all projects in my team so I know what work is in progress.
- I cannot create, edit, or delete projects — that is reserved for Owners and Admins.

**Edge cases:**
- If a project name already has a slug in use within the same team, the slug should be made unique (append `-2`, `-3`, etc.).
- Deleting a project should soft-delete it so it can be recovered.

## Requirements

### Must-Have (P0)

| # | Requirement | Acceptance Criteria |
|---|---|---|
| 1 | **Project model** with `name` (required), `description` (nullable text), `slug` (unique per team, auto-generated from name), `url` (nullable, valid URL), `team_id` (FK) | Model exists with correct fillable, casts, and relationships |
| 2 | **Migration** creating `projects` table with proper columns, indexes, foreign key to teams, soft deletes, and a unique composite index on `(team_id, slug)` | Migration runs cleanly, schema matches spec |
| 3 | **Team → Projects relationship** (`Team::projects()` returns HasMany) | `$team->projects` returns the team's projects |
| 4 | **Slug auto-generation** from name, unique within the team (not globally) | Creating two projects with the same name in different teams produces the same slug; same name in one team produces `slug-2` |
| 5 | **ProjectPolicy** — `viewAny`/`view`: any team member. `create`/`update`/`delete`: Owner or Admin only. | Policy methods return correct booleans based on role |
| 6 | **TeamPermission expansion** — Add `CreateProject`, `UpdateProject`, `DeleteProject` to the enum. Wire into TeamRole permissions (Owner gets all, Admin gets all three, Member gets none). | Enum cases exist, role permissions updated |
| 7 | **Filament ProjectResource** — List page (table with name, description, url, created date), Create page, Edit page. Scoped to current tenant. | CRUD works in the admin panel within a team |
| 8 | **Factory** for Project with sensible defaults | `Project::factory()->create()` works in tests |
| 9 | **Pest tests** — Model relationships, slug generation, policy authorization, Filament resource CRUD | All tests pass |

### Nice-to-Have (P1)

| # | Requirement | Notes |
|---|---|---|
| 1 | **Slug updates when name changes** (matching Team behaviour) | Keeps URLs consistent with names |
| 2 | **URL validation** — validate as a proper URL when provided | Prevents invalid data |
| 3 | **Empty state** on the projects list page with a call to action to create the first project | Better UX for new teams |

### Future Considerations (P2)

| # | Requirement | Notes |
|---|---|---|
| 1 | **Forms relationship** — `Project::forms()` HasMany | Will be added when the Form model is built |
| 2 | **Project-level permissions** — restrict which members can access which projects | May be needed for larger teams |
| 3 | **Project archiving** — soft status for hiding without deleting | Separate from soft deletes |
| 4 | **Project dashboard** — a landing page showing project stats, recent forms, activity | After Forms exist |

## Technical Notes

- **Slug generation**: The existing `GeneratesUniqueTeamSlugs` trait on Team generates globally unique slugs. Projects need slugs unique *within a team*, not globally. Either adapt the trait or create a similar approach scoped to `team_id`.
- **Filament tenancy**: The admin panel already uses Team as the tenant. The ProjectResource should automatically scope to the current tenant via Filament's `HasCurrentTenantLabel` / tenant scoping.
- **Soft deletes**: Follow the Team model pattern — include `SoftDeletes` trait.
- **Route key**: Use `slug` as the route key name for URL-friendly routes (matching Team).

## Open Questions

- **[Engineering]** Should we reuse `GeneratesUniqueTeamSlugs` and add a team-scoping parameter, or create a separate concern for project slugs? Leaning toward a new `GeneratesUniqueProjectSlugs` trait or a more generic `GeneratesUniqueSlugs` trait that accepts a scope.

## Success Metrics

- Project CRUD works end-to-end in the Filament panel
- All tests pass
- The data model is ready for Forms to be added in TASK-3
