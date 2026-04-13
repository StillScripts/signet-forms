# TASK-7: Form Versioning

## Problem Statement

The form builder currently stores field definitions directly in the `forms.fields` JSON column. Every save overwrites the previous state with no history. Undo/redo is implemented as an in-memory Livewire array that is lost on page reload or navigation. This means users cannot recover previous versions of a form, there is no audit trail of changes, and future form submissions cannot be tied back to the exact form structure they were submitted against.

Target users are team owners and admins who build and iterate on forms. They need confidence that saving won't destroy previous work, and that they can see what changed over time.

## Goals

1. **Append-only version history** — Every save creates a new `FormVersion` record. No form data is ever overwritten or lost.
2. **Persistent undo/redo** — Replace the in-memory history stack with DB-backed versions. Undo/redo survives page reloads and works across sessions.
3. **Version awareness in the builder** — The builder header shows the current version (e.g., "v4 (23 mins ago)") so users always know where they are.
4. **Submission-ready architecture** — Form versions have a stable ID that future submission records can reference, ensuring a submission is always tied to the exact form structure it was collected against.

## Non-Goals

- **Changes to the `forms` table schema** — The existing `fields` column stays as-is. `form_versions` is additive, not a replacement of the forms table structure.
- **Named drafts or branches** — Version numbering is auto-increment only. Named drafts (e.g., "Q4 Campaign v2") are a future consideration.
- **Version comparison/diff UI** — No side-by-side diff view. Users can see version numbers and timestamps, not field-level diffs.
- **Settings/configuration versioning** — Only the `fields` JSON is versioned. Form-level settings (like column count) are not included in this task.
- **Rollback confirmation UI** — Undo navigates to the previous version directly. A dedicated "version history" panel with browse/restore is a future enhancement.
- **Publishing workflow** — Tying `is_published` to a specific version is a future task. This task versions all saves equally.

## User Stories

**As a form creator (Owner/Admin):**

- I want every save to create a new version so I never lose previous work.
- I want to undo my last save and restore the previous version's fields, even after refreshing the page.
- I want to redo a save I just undid, so I can move forward again if the undo was a mistake.
- I want to see which version I'm currently viewing (e.g., "v4 (23 mins ago)") so I know where I am in the history.
- I want to reset the form to the last saved state so I can discard unsaved in-session changes.

**Edge cases:**

- A brand-new form with no saves yet should show no version label (or "Draft — unsaved").
- Saving a form for the first time creates version 1.
- Undoing past version 1 should be disabled (no earlier version exists).
- Redoing past the latest version should be disabled.
- If I undo to v3 and then make new edits and save, the new save becomes v6 (append-only — no branch/fork). Redo is no longer available since the timeline moved forward.
- The `forms.fields` column should always reflect the current/latest version's fields for backward compatibility (e.g., the top-level forms list, form preview, and any code that reads `$form->fields` directly).

## Requirements

### Must-Have (P0)

| # | Requirement | Acceptance Criteria |
|---|---|---|
| 1 | **`form_versions` migration** — Create the `form_versions` table with: `id`, `form_id` (FK to forms), `version` (unsigned integer), `fields` (JSON), `created_at`. Unique constraint on `(form_id, version)`. | Migration runs cleanly. Table exists with correct columns and constraints. |
| 2 | **`FormVersion` model** — Eloquent model with `belongsTo(Form)` relationship. Form model gets a `hasMany(FormVersion)` and a `latestVersion()` helper (latest by version number). | Models and relationships work. `$form->versions` returns all versions. `$form->latestVersion()` returns the most recent. |
| 3 | **Save creates a version** — `FormBuilderPage::save()` creates a new `FormVersion` record with the next version number and the current fields JSON. Also updates `forms.fields` to keep it in sync. | Saving a form increments the version count. `form_versions` has a new row. `forms.fields` matches the latest version's fields. |
| 4 | **Undo loads previous version** — `undo()` loads the previous `FormVersion` (current version - 1) and restores its fields to the builder. | Clicking undo after saving v3 loads v2's fields. Button is disabled when on v1 or when no versions exist. |
| 5 | **Redo loads next version** — `redo()` loads the next `FormVersion` (current version + 1) and restores its fields. | Clicking redo after undoing to v2 (when v3 exists) loads v3's fields. Button is disabled when on the latest version. |
| 6 | **Version label in builder header** — Show the current version number and relative timestamp next to the form name (e.g., "v4 (23 mins ago)"). | Label displays correctly. Updates after save. Shows nothing or "Unsaved" for forms with no versions. |
| 7 | **Remove in-memory history** — Remove the `$history` and `$historyIndex` properties and `pushHistory()`/`restoreFromHistory()` methods from `FormBuilderPage`. Undo/redo is now entirely version-based. | No in-memory history array. Undo/redo buttons wire to the new version-based methods. |
| 8 | **Reset still works** — "Reset" discards unsaved in-session changes and reloads from the current version's saved fields (not undo to a previous version). | Clicking reset after making unsaved edits restores the fields from the current version. |
| 9 | **Existing forms get a v1** — A migration or seeder creates a `FormVersion` v1 for every existing form that has non-null fields. | After migration, every form with fields has exactly one version record. |

### Nice-to-Have (P1)

| # | Requirement | Acceptance Criteria |
|---|---|---|
| 10 | **Unsaved changes tracking** — Keep the "Unsaved changes" badge. It should appear when the builder's fields differ from the current version's saved fields. | Badge shows when fields are modified. Disappears after save. |
| 11 | **Version count on form view page** — Show the total number of versions somewhere on the form's view/detail page. | Form view page displays version count (e.g., "12 versions"). |

### Future Considerations (P2)

| # | Requirement |
|---|---|
| 12 | **Named drafts** — Allow users to name/tag a version (e.g., "Q4 Campaign") for easy reference. |
| 13 | **Version history panel** — A slide-over or modal listing all versions with timestamps, allowing users to browse and restore any version. |
| 14 | **Version diffing** — Side-by-side comparison showing which fields were added, removed, or modified between two versions. |
| 15 | **Submission → version FK** — Future `form_submissions` table references `form_version_id` so each submission is tied to the exact form structure. |
| 16 | **Settings versioning** — Version form-level configuration (column count, styling, etc.) alongside fields. |
| 17 | **Publishing tied to versions** — `is_published` references a specific version, allowing draft edits without affecting the live form. |

## Data Model

### `form_versions` table

```
id              — bigint, PK
form_id         — bigint, FK → forms.id, cascade on delete
version         — unsigned integer (auto-incremented per form, starting at 1)
fields          — JSON (snapshot of the form's fields at this version)
created_at      — timestamp

UNIQUE(form_id, version)
INDEX(form_id, version)  — for fast lookups of latest/adjacent versions
```

### Relationships

- `Form hasMany FormVersion` (ordered by version)
- `FormVersion belongsTo Form`
- `Form::latestVersion()` — convenience method returning the highest-versioned record

## Save Flow

```
User clicks Save
  → Strip internal metadata (_manual_key) from fields
  → Determine next version number: max(form's versions) + 1, or 1 if none
  → Create FormVersion { form_id, version, fields }
  → Update forms.fields = saved fields (backward compat)
  → Set currentVersion on the page component
  → Clear hasUnsavedChanges
  → Show notification: "Saved as v{N}"
```

## Undo/Redo Flow

```
Undo:
  → If currentVersion <= 1, do nothing (disabled)
  → Load FormVersion where form_id = form AND version = currentVersion - 1
  → Set builder fields = loaded version's fields
  → Decrement currentVersion on page
  → Mark hasUnsavedChanges = false (viewing a saved version)

Redo:
  → If currentVersion >= latestVersion, do nothing (disabled)
  → Load FormVersion where form_id = form AND version = currentVersion + 1
  → Set builder fields = loaded version's fields
  → Increment currentVersion on page
  → Mark hasUnsavedChanges = false (viewing a saved version)

Save after Undo (e.g., on v3, undo to v2, edit, save):
  → New version = latestVersion + 1 (append-only, no branching)
  → Redo becomes unavailable (currentVersion is now the latest)
```

## Success Metrics

- Every form save produces a version record — no data loss on overwrites.
- Undo/redo works across page reloads (manually testable).
- Builder header shows correct version label with relative time.
- All existing tests continue to pass (backward compatibility via `forms.fields` sync).

## Open Questions

| # | Question | Owner |
|---|---|---|
| 1 | Should there be a limit on the number of versions stored per form? (e.g., keep last 100, prune older ones) | Engineering |
| 2 | When undoing to v2 and saving new changes as v6, should the notification say "Saved as v6" or "Saved as v6 (based on v2)"? | Design |
