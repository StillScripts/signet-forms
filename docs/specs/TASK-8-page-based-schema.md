# TASK-8: Page-Based Schema Structure

## Problem Statement

The form builder currently stores fields as a flat JSON array in `forms.fields` and `form_versions.fields`. This structure only supports single-page forms. To support multi-page wizard forms — where each page has its own title, fields, and submit button text — the data model needs to wrap fields inside a `pages` array. This is a foundational schema change that affects the Form model, FormVersion model, builder UI, migrations, and tests.

Target users are form creators (team owners and admins) who need both simple single-page forms and multi-step wizard forms for longer workflows like registration, onboarding, or surveys.

## Goals

1. **Page-based schema** — Restructure the form data from a flat `fields` array to `{ "pages": [{ "id", "title", "fields", "submit_button_text" }] }`. Single-page forms have one page; multi-page forms have multiple pages rendered as wizard steps.
2. **Column rename** — Rename `fields` → `schema` on both `forms` and `form_versions` tables to reflect the broader purpose of the column.
3. **Multi-page builder UI** — Add page management to the form builder: add/remove/reorder pages, page tabs or navigation, per-page title editing. Should feel natural alongside the existing builder, not bolted on.
4. **Data migration** — Migrate all existing forms and form_versions from the flat structure to the page-based structure without data loss.
5. **Smart submit button defaults** — Each page stores `submit_button_text`. Defaults: "Continue" for non-last pages, "Submit" for last/single page.

## Non-Goals

- **Settings column** — No `settings` JSON column on forms for now. Form-level configuration beyond schema is a future task.
- **Conditional page logic** — No skip/branch logic between pages based on field values.
- **Page-level validation rules** — No per-page validation configuration beyond individual field requirements.
- **Progress indicator customisation** — The wizard progress indicator is rendered with default styling; no user configuration of step labels or icons.

## User Stories

**As a form creator (Owner/Admin):**

- I want my existing single-page forms to keep working unchanged after this migration.
- I want to add new pages to a form so I can split long forms into logical steps.
- I want to remove pages I no longer need.
- I want to reorder pages to control the flow of my multi-step form.
- I want to set a title for each page so respondents understand each step.
- I want each page's submit button to say "Continue" by default (or "Submit" on the last page) so the UX is clear.
- I want to customise the submit button text per page if needed.
- I want to see a clear indicator of whether my form is single-page or multi-page.
- I want the preview tab to show wizard-style step navigation for multi-page forms.

**Edge cases:**

- A form with no pages (brand new, never saved) should initialise with one empty page.
- Removing the last page should not be allowed — a form must always have at least one page.
- Moving fields between pages is not required in this task (future enhancement).
- The version label, undo/redo, and save flow should continue working with the new schema structure.

## Requirements

### Must-Have (P0)

| # | Requirement | Acceptance Criteria |
|---|---|---|
| 1 | **Rename `fields` → `schema`** — Migration to rename the column on both `forms` and `form_versions` tables. | Column is renamed. All model references updated. Old `fields` accessor removed. |
| 2 | **Page-based schema structure** — The `schema` column stores `{ "pages": [{ "id": "uuid", "title": null, "fields": [...], "submit_button_text": null }] }`. | Schema validates to the expected structure. Single-page forms have one page entry. |
| 3 | **Data migration** — Migrate existing `forms.fields` and `form_versions.fields` data into the new page-based structure. Each existing flat fields array becomes a single page with a generated UUID and null title. | All existing forms and versions retain their fields in the new structure. No data loss. |
| 4 | **Model updates** — Update Form and FormVersion models: rename `fields` cast to `schema`, update `latestVersion()`, update fillable. | Models work with `schema` column. `$form->schema` returns the page-based structure. |
| 5 | **Builder works with pages** — `FormBuilderPage` component manages pages. `$this->fields` becomes the active page's fields. Page selection, add page, remove page. | Builder loads and saves the page-based schema. Adding/removing fields works within the active page. |
| 6 | **Page tabs in builder** — The builder shows page tabs (e.g., "Page 1", "Page 2") above the canvas. Users can switch between pages to edit each page's fields. | Clicking a page tab loads that page's fields into the canvas. Active page is visually indicated. |
| 7 | **Add/remove pages** — Users can add new pages and remove pages (minimum 1 page). | Add page creates a new empty page. Remove page is disabled when only one page exists. |
| 8 | **Page metadata editing** — Each page has editable `title` (shown in page tab/wizard step label), `heading` (displayed prominently at top of page), `subheading` (displayed below heading), and `submit_button_text`. | Editing page metadata updates the schema. Null title displays as "Page N". Heading/subheading are optional. |
| 9 | **Submit button text per page** — Each page stores `submit_button_text`. Defaults: "Continue" for non-last pages, "Submit" for last/single page. | Default text displays correctly. Custom text persists through save/reload. |
| 10 | **Save/load with new schema** — Save creates a FormVersion with the full page-based schema. Load restores all pages. | Round-trip save/load preserves all pages and their fields. |
| 11 | **Undo/redo works with schema** — Version-based undo/redo navigates the full schema (all pages), not just one page's fields. | Undo/redo restores the complete page-based schema. |
| 12 | **Preview tab supports pages** — Multi-page forms show wizard-style step navigation in preview. Single-page forms render as before. | Preview shows steps for multi-page forms. Single-page forms show a flat form. |
| 13 | **Update all tests** — All existing tests pass with the new schema structure. New tests cover multi-page behaviour. | All tests green. |

### Nice-to-Have (P1)

| # | Requirement | Acceptance Criteria |
|---|---|---|
| 14 | **Page reordering** — Drag or button-based reordering of pages. | Pages can be reordered. Order persists through save. |
| 15 | **Description on builder page** — Form description editable from the builder header or a settings panel. | Description can be edited without leaving the builder. |

### Future Considerations (P2)

| # | Requirement |
|---|---|
| 16 | **Move fields between pages** — Drag a field from one page to another. |
| 17 | **Conditional page navigation** — Skip pages based on field values. |
| 18 | **Page-level settings** — Per-page column count, description, or layout options. |
| 19 | **Settings column** — `forms.settings` JSON column for form-wide configuration. |

## Data Model

### Schema structure (stored in `forms.schema` and `form_versions.schema`)

```json
{
  "pages": [
    {
      "id": "uuid-v4",
      "title": "Page Title (optional, nullable — used in page tabs and wizard step labels)",
      "heading": "Page Heading (optional, nullable — displayed prominently at top of page)",
      "subheading": "Page Subheading (optional, nullable — displayed below heading)",
      "submit_button_text": null,
      "fields": [
        {
          "type": "text-input",
          "key": "full_name",
          "sort": 0,
          "data": { "label": "Full Name", "placeholder": "", "is_required": true, "column_span": 1, ... }
        }
      ]
    }
  ]
}
```

### Submit button text defaults

| Scenario | Default text |
|---|---|
| Single-page form (1 page) | "Submit" |
| Multi-page: non-last page | "Continue" |
| Multi-page: last page | "Submit" |
| Custom text set | Use custom text |

### Column changes

| Table | Old column | New column |
|---|---|---|
| `forms` | `fields` (JSON, nullable) | `schema` (JSON, nullable) |
| `form_versions` | `fields` (JSON, nullable) | `schema` (JSON, nullable) |

## Save Flow

```
User clicks Save
  -> Collect all pages with their fields (strip _manual_key from each field)
  -> Build schema: { "pages": [ ... ] }
  -> Create FormVersion { form_id, version, schema }
  -> Update forms.schema = saved schema (backward compat)
  -> Set currentVersion on the page component
  -> Clear hasUnsavedChanges
  -> Notification: "Saved as v{N}"
```

## Builder UI Flow

```
Page Tabs Bar (above canvas):
  [Page 1] [Page 2] [+ Add Page]

Active page's fields shown in canvas (existing drag-to-reorder, add/remove).
Field palette adds to the active page.
Right panel shows field settings for selected field on the active page.

Page settings (title, heading, subheading, submit button text) shown:
  - In the right panel when a page tab is selected (no field selected)
  - Or via inline editing in the page tab area for title
```

## Success Metrics

- All existing forms load correctly with the new schema structure.
- Single-page forms behave identically to before.
- Multi-page forms can be created, saved, and previewed as wizard steps.
- All tests pass (existing + new).
- Undo/redo works across the full schema.

## Open Questions

| # | Question | Owner |
|---|---|---|
| 1 | Should the page reorder be drag-based or arrow-button-based? | Design |
| 2 | Should multi-page preview use Filament's built-in Wizard component or a custom stepper? | Engineering |
