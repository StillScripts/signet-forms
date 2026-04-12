# TASK-4: Visual Form Builder Page

## Problem Statement

Signet Forms has a Form model (TASK-3) that stores field definitions as a JSON column, but there is no way to actually build forms visually. Users currently see a basic CRUD interface for Form metadata (name, description, published toggle) but cannot add or configure form fields. The core value proposition of Signet Forms — letting non-technical users build web forms visually — is blocked until this builder exists.

The target users are primarily non-technical team members building forms for their websites (contact forms, surveys, registration forms), though developers may also use it as a GUI alternative to writing Filament code directly.

## Goals

1. **Visual form builder** — Users can build forms through a three-panel interface: field palette (left), canvas (center), field settings (right), without writing any code.
2. **Field type coverage** — Support the most common Filament field types: Text Input, Textarea, Number, Select, Checkbox, Radio Group, Toggle, Date Picker, File Upload, and Rich Text Editor.
3. **Field configuration** — Each field can be configured with label, field name (slug), placeholder, helper text, default value, column span, and validation rules (required, min/max length where applicable).
4. **Multi-column layout** — Users can control the form grid columns (1-4) and set per-field column spans, enabling side-by-side field layouts.
5. **Live preview** — A Preview tab renders the form using actual Filament form components so users see exactly what end users will see.
6. **Drag-to-reorder** — Fields on the canvas can be reordered by dragging.
7. **Save and persist** — Field definitions are saved to the Form model's `fields` JSON column and can be reloaded for continued editing.

## Non-Goals

- **Form templates** — Starting from a preset template (Contact Us, Feedback, etc.) is deferred to TASK-5.
- **Form submissions/responses** — This task builds forms, not the submission handling, storage, or analytics. That is a future task.
- **Public form rendering** — Rendering the form on a public-facing page for end users to fill out is a separate task. The builder and preview are admin-only.
- **Conditional logic** — Show/hide fields based on other field values is a future enhancement.
- **Multi-page/step forms** — All fields are on a single page. Wizard-style multi-step forms are a future consideration.
- **Custom CSS/theming per form** — Forms use the default Filament styling.
- **Code export tab** — A "Code" tab showing the JSON or Filament PHP code is a nice-to-have, not a requirement. Include only if implementation is straightforward.

## User Stories

**As a team Owner/Admin (form creator):**
- I want to open a form and see a visual builder so I can add fields without writing code.
- I want to click a field type from the palette to add it to my form so I don't have to configure anything upfront.
- I want to click a field on the canvas to see its settings on the right so I can configure the label, placeholder, validation, etc.
- I want to drag fields to reorder them so I can arrange the form logically.
- I want to set the number of columns for the form layout so fields can appear side by side.
- I want to set column span per field so a field can take full width or half width.
- I want to mark fields as required so form respondents must fill them in.
- I want to preview my form as it will appear to end users so I can verify the design before publishing.
- I want to save my work and come back later to continue editing.
- I want to undo/redo changes so I can recover from mistakes.
- I want to reset the form to its last saved state if I've made changes I don't want to keep.
- I want to delete a field from the canvas when I no longer need it.

**As a team Member (viewer):**
- I can view the form builder in read-only mode (governed by existing policy — Members cannot update forms).

**Edge cases:**
- Adding a field when the canvas is empty should show the field and hide the empty state.
- Field names (slugs) should be auto-generated from the label but editable, and unique within the form.
- Saving a form with no fields should be allowed (empty form).
- The builder should handle the full set of field settings gracefully — fields that don't support a setting (e.g., placeholder on a checkbox) should not show that setting option.

## Requirements

### Must-Have (P0)

| # | Requirement | Acceptance Criteria |
|---|---|---|
| 1 | **Custom Livewire page** — `FormBuilderPage` registered as a route under `FormResource` at `/{record}/builder` | Page loads within the Filament panel, receives the Form record and its parent Project |
| 2 | **Three-panel layout** — Left: field palette, Center: canvas, Right: field settings (shown when a field is selected) | All three panels render; right panel only appears when a field is selected |
| 3 | **Field palette** — Grouped by category (Basic Fields, Date & Time, Advanced). Click a field type to add it to the canvas with sensible defaults | Clicking a palette item appends a new field to the canvas |
| 4 | **Supported field types** — Text Input, Textarea, Number, Select, Checkbox, Radio Group, Toggle, Date Picker, File Upload, Rich Text Editor | All 10 types appear in the palette and can be added to the canvas |
| 5 | **Canvas** — Shows all added fields as cards with field type icon, label, field name, and a preview of the field. Empty state shown when no fields exist | Canvas reflects current field list; empty state shows "Start building your form" message |
| 6 | **Field settings panel** — When a field is selected, the right panel shows editable settings: label, field name, placeholder, helper text, default value, column span, required toggle | Editing a setting updates the field in real-time on the canvas |
| 7 | **Per-field-type settings** — Only show settings relevant to the field type (e.g., no placeholder for Checkbox/Toggle; Select/Radio show options editor) | Settings panel adapts to the selected field type |
| 8 | **Column layout** — Header has a columns selector (1-4). Each field has a column span setting (1 to max columns) | Changing columns updates the canvas grid; field column span respects the grid |
| 9 | **Drag-to-reorder** — Fields on the canvas can be reordered via drag handles | Dragging a field changes its position; new order persists on save |
| 10 | **Save** — Save button persists the field definitions to the Form model's `fields` JSON column | After save, reloading the page shows the same fields in the same order |
| 11 | **Header bar** — Back button (returns to form view), form name display, published badge, unsaved changes indicator, columns selector, save button | All header elements render and function correctly |
| 12 | **Delete field** — Each field card has a delete button to remove it from the canvas | Clicking delete removes the field; can be undone before saving |
| 13 | **Field name auto-generation** — When the user types a label, the field name auto-generates as a slug (e.g., "Date of Birth" → "dob" or "date_of_birth"). Field name is editable. | Auto-slug works on new fields; editing the field name directly overrides auto-slug |
| 14 | **Preview tab** — Renders the current field definitions as an actual Filament form (read-only) | Preview shows a realistic representation of the form using Filament components |
| 15 | **Tests** — Pest tests covering: page loads, adding fields, saving fields, reloading saved fields, deleting fields | All tests pass |

### Nice-to-Have (P1)

| # | Requirement | Notes |
|---|---|---|
| 1 | **Undo/redo** — Track field changes in a history stack; undo/redo buttons in the header | Enables quick recovery from mistakes |
| 2 | **Reset** — Button to discard unsaved changes and revert to last saved state | Safety net for users |
| 3 | **Code tab** — Shows the form field definitions as JSON or Filament Builder PHP syntax with copy button | Useful for developers; skip if complex |
| 4 | **Field count indicator** — "N fields" display in the header | Quick reference for form size |
| 5 | **Validation settings** — Required toggle, min/max length for text fields, min/max value for number fields | Important for production forms but builder works without it |

### Future Considerations (P2)

| # | Requirement | Notes |
|---|---|---|
| 1 | **All Filament field types** — Color Picker, Date-Time Picker, Time Picker, Key-Value, Tags, Markdown Editor, etc. | Expand the palette over time |
| 2 | **Conditional logic** — Show/hide fields based on other field values | Common form builder feature |
| 3 | **Multi-page/wizard forms** — Split form into steps/pages | For complex forms |
| 4 | **Custom validation rules** — Regex patterns, custom messages, cross-field validation | Advanced use cases |
| 5 | **Form templates** — Start from presets (TASK-5) | Already tracked |
| 6 | **Form submissions** — Collect, store, and display form responses | Core future feature |
| 7 | **Public form rendering** — Render forms on a public URL for end users | Core future feature |

## Technical Notes

### Data Structure

The `fields` JSON column stores an array of field definitions:

```json
[
  {
    "type": "text-input",
    "key": "first_name",
    "sort": 0,
    "data": {
      "label": "First Name",
      "placeholder": "Enter your first name...",
      "helper_text": null,
      "default_value": null,
      "column_span": 1,
      "is_required": false,
      "validation": {}
    }
  },
  {
    "type": "select",
    "key": "country",
    "sort": 1,
    "data": {
      "label": "Country",
      "placeholder": "Select a country...",
      "helper_text": null,
      "default_value": null,
      "column_span": 2,
      "is_required": true,
      "options": [
        {"label": "Australia", "value": "au"},
        {"label": "United States", "value": "us"}
      ],
      "validation": {}
    }
  }
]
```

Each field type maps to a Filament form component. The `type` key identifies which component to render. The `key` is the field name/slug used in form submission data. The `data` object holds all configuration.

### Architecture

- **Page class**: A custom Filament page (`FormBuilderPage`) extending `Filament\Resources\Pages\Page` with a custom Blade view. This is a Livewire component with full interactivity.
- **Blade view**: Three-panel layout using Tailwind CSS grid/flex. Alpine.js for client-side interactions (drag-and-drop, tab switching). Livewire for server-side state (save, field CRUD).
- **Field type registry**: A PHP enum or config array defining each field type's metadata (label, icon, category, available settings). This makes adding new field types straightforward.
- **Preview rendering**: The Preview tab uses Filament's `Schema` to render the fields as actual Filament form components in a read-only/disabled state.
- **Drag-and-drop**: Use Alpine.js with SortableJS (or wire:sort if supported in Livewire 4) for reordering.

### Routing

The builder page registers as a new page in `FormResource::getPages()`:

```
{tenant}/projects/{project}/forms/{record}/builder
```

The existing View page should link to the builder (e.g., "Open Builder" action button).

## Open Questions

- **[Engineering]** Should drag-and-drop use `wire:sort` (Livewire 4 native) or Alpine.js + SortableJS? Need to verify Livewire 4 sort support with the current setup.
- **[Design]** Should the builder replace the standard Edit page entirely, or coexist as a separate page? Current spec has it as a separate route — the Edit page stays for metadata (name, description, published).

## Success Metrics

- Users can build a form with 5+ fields of mixed types, save it, and reload it without data loss
- Preview tab renders a faithful representation of the form using Filament components
- All new tests pass alongside existing 87 tests
- The builder page loads within the Filament panel with proper tenant/project scoping
