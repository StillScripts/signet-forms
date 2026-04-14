# TASK-17: Layout Elements in Builder

## Problem Statement

The form builder currently only supports data-collecting field types (text input, select, etc.). Form authors have no way to add non-data content — section headers, explanatory text, dividers, or images — to guide respondents through longer forms. This forces awkward workarounds (using a readonly field as a header) and produces cluttered submission data when authors try to use existing fields for layout purposes.

## Goals

1. Add four non-data layout elements to the builder palette: `section_header`, `divider`, `instructional_text`, `image`.
2. Render these elements in both the builder canvas/preview and the public form exactly like any other field.
3. Exclude layout elements from submission `data` — they must never appear in `Submission::data`.
4. Exclude layout elements from validation — they have no `is_required` concept.
5. Allow layout elements to span multiple columns just like data fields.
6. Group layout elements under a new "Layout" category in the palette.

## Non-Goals

- **Rich text / WYSIWYG editing of instructional text** — plain text with basic markdown-like support via the existing `instructional_text` render path is out of scope. Store and render plain text only.
- **Image uploads** — `image` stores a URL string only. No upload handler, no file storage, no asset picker. Authors paste a URL.
- **Conditional visibility on layout elements** — TASK-18 (logic engine) will handle show/hide rules across all element types including layout.
- **Nested sections / collapsible groups** — `section_header` is a visual heading, not a grouping container.
- **Custom dividers (colour, thickness, style)** — one simple horizontal rule is enough for now.

## User Stories

- As a form author, I want to add a section heading above a group of related fields so respondents understand what each section covers.
- As a form author, I want to add instructional text between fields to explain why I'm asking for information.
- As a form author, I want to insert a divider to visually separate unrelated parts of a long form.
- As a form author, I want to include an image in the form (e.g., a logo or diagram) so respondents have visual context.
- As a reviewer looking at a submission, I want submission data to only contain answers — not layout content that was never filled in by the respondent.

## Requirements

### Must Have

1. Four new cases on `FormFieldType`: `SectionHeader`, `Divider`, `InstructionalText`, `Image`. String values: `section-header`, `divider`, `instructional-text`, `image`.
2. New `isLayout(): bool` method on `FormFieldType` returning `true` for the four layout types.
3. New `Layout` category returned by `FormFieldType::category()`.
4. Per-type `defaultData()`:
    - `section-header`: `heading` (default `'Section Heading'`), `subheading` (default `null`), `column_span` (default 1).
    - `divider`: `column_span` (default 1 — but renderer always spans full width visually).
    - `instructional-text`: `content` (default `'Add instructional text here.'`), `column_span` (default 1).
    - `image`: `url` (default empty string), `alt` (default empty string), `column_span` (default 1).
5. `hasPlaceholder()`, `hasOptions()`, `hasMinMax()` all return `false` for layout elements.
6. Builder palette: layout elements appear under a "Layout" group heading.
7. Builder canvas: each layout element renders a compact card showing its type and content summary (e.g., heading text, URL, preview of instructional text), with the same selection/delete affordances as data fields.
8. Right-hand field settings panel: shows type-appropriate inputs only. No label/placeholder/required/min/max for layout elements.
9. Builder preview tab: renders the layout element using Filament schema components (`Text`, `Html`, `Image`) inside the same schema tree as data fields.
10. Public form renderer (`PublicFormPage`): renders the same Filament schema components so layout elements appear inline on the public form.
11. Submission pipeline: layout elements produce no keys in the `data` array stored on `submissions.data`.
12. Save/load/versioning round-trip: layout elements persist correctly in `form_versions.schema` and `forms.schema`.
13. Layout elements work identically on single-page and multi-page forms.

### Should Have

- Layout element labels in the palette use human-friendly names: "Section Header", "Divider", "Instructional Text", "Image".
- Icons in the palette pick suitable Heroicons (e.g., heading icon, minus/line icon, document-text icon, photo icon).

### Could Have (deferred)

- Markdown support inside `instructional_text`.
- Image alignment (left/centre/right).
- Section heading levels (h2/h3/h4).

## Implementation Sketch

- **Enum**: extend `App\Enums\FormFieldType` with four new cases and an `isLayout()` method. Adjust `defaultData()` to branch on `isLayout()` before the existing data-field defaults.
- **Builder** (`FormBuilderPage`): update `buildFilamentComponent()` to match the new types and return schema components (`Section`/`Text`, `Html`, `Text`, `Image`). Update `fieldSettingsSchema()` to render type-specific editors for layout fields (heading + subheading, content textarea, url + alt).
- **Canvas blade**: conditional rendering inside the field-card loop — if the field type is layout, show a minimal summary preview rather than the placeholder box.
- **Public renderer** (`PublicFormPage`): mirror the `buildFilamentComponent` changes so public forms render layout elements.
- **Data exclusion**: relies on the fact that Filament's `Text`, `Html`, `Image` schema components do not bind to state, so `$this->form->getState()` naturally omits them. No explicit filtering needed. Tests will assert this behaviour.

## Success Metrics

- Form authors can add all four layout element types from the palette in one click each.
- A form with a mix of data fields and layout elements saves and reloads with all content intact.
- Submitting a form with layout elements produces a `Submission::data` array containing only the data-field keys.
- Layout elements do not trigger required-field validation errors.

## Open Questions

- Should image URLs be validated as real URLs during save? **Decision: no** — lax validation for now; authors paste whatever URL they control. Defer hardening to a later security pass.
- Should `section_header` auto-span the full width regardless of column setting? **Decision: no** — respect `column_span` like other elements so authors can place two headings side-by-side if they want. The divider can behave the same way.

## Testing Strategy

Feature tests cover:

1. Builder adds each of the four layout element types with correct defaults.
2. Layout elements round-trip through save/load.
3. Public form page renders layout element content (heading text, instructional text, image alt).
4. Submitting a form containing layout elements stores only data-field keys in `Submission::data`.
5. A required data field co-existing with layout elements still validates correctly; layout elements never trigger required errors.
6. `FormFieldType::grouped()` includes a `Layout` category containing all four types.
